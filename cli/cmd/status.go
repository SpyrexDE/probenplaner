package cmd

import (
	"fmt"
	"strings"

	"github.com/charmbracelet/lipgloss"
	"github.com/spf13/cobra"
)

var statusCmd = &cobra.Command{
	Use:   "status",
	Short: "Show system status dashboard",
	Run: func(cmd *cobra.Command, args []string) {
		printStatus()
	},
}

func printStatus() {
	// --- Gum / Charm Style Definitions ---
	// Colors from user request: Pink (212), Purple (57), White (255), Grey (240)
	colorPink := lipgloss.Color("212")   // #FF5FAF
	colorPurple := lipgloss.Color("57")  // #5F00FF
	colorWhite := lipgloss.Color("255")  // #EEEEEE
	colorGrey := lipgloss.Color("240")   // #585858
	colorGreen := lipgloss.Color("46")   // #00FF00
	colorRed := lipgloss.Color("196")    // #FF0000
	colorYellow := lipgloss.Color("226") // #FFFF00

	// Borders
	boxStyle := lipgloss.NewStyle().
		Border(lipgloss.DoubleBorder()).
		BorderForeground(colorPink).
		Padding(0, 1).
		MarginRight(1)

	// Text Styles
	titleStyle := lipgloss.NewStyle().
		Foreground(colorWhite).
		Background(colorPurple).
		Padding(0, 1).
		MarginBottom(1).
		Bold(true)

	headerStyle := lipgloss.NewStyle().
		Foreground(colorPink).
		Bold(true)

	// Status Indicators
	dotGreen := lipgloss.NewStyle().Foreground(colorGreen).SetString("●")
	dotRed := lipgloss.NewStyle().Foreground(colorRed).SetString("●")
	dotYellow := lipgloss.NewStyle().Foreground(colorYellow).SetString("●")
	dotGrey := lipgloss.NewStyle().Foreground(colorGrey).SetString("○")

	// --- Data Gathering ---
	envStr := strings.ToUpper(GetEnv())
	gitVer, _ := RunCommandCapture("git", "describe", "--tags", "--always")
	gitVer = strings.TrimSpace(gitVer)
	if gitVer == "" {
		gitVer = "dev"
	}

	dockerRunning := true
	if _, err := RunCommandCapture("docker", "version"); err != nil {
		dockerRunning = false
	}

	env := GetEnv()
	composeFiles := GetComposeFile(env)

	// 1. Defined Services
	configArgs := append(append([]string{"compose"}, composeFiles...), "config", "--services")
	definedSvcsOut, _ := RunCommandCaptureSilentOutput("docker", configArgs...)
	definedSvcs := strings.Split(strings.TrimSpace(definedSvcsOut), "\n")

	// 2. Container Details (Service|Image|ID|State|Status|Ports)
	psArgs := append(append([]string{"compose"}, composeFiles...), "ps", "-a", "--format", "{{.Service}}|{{.Image}}|{{.ID}}|{{.State}}|{{.Status}}|{{.Ports}}")
	psOut, _ := RunCommandCaptureSilentOutput("docker", psArgs...)
	psLines := strings.Split(strings.TrimSpace(psOut), "\n")

	type SvcInfo struct {
		Image, ID, State, Status, Ports string
	}
	svcMap := make(map[string]SvcInfo)

	for _, line := range psLines {
		parts := strings.Split(line, "|")
		if len(parts) >= 6 {
			svcMap[parts[0]] = SvcInfo{
				Image:  parts[1],
				ID:     parts[2],
				State:  strings.ToLower(parts[3]),
				Status: parts[4],
				Ports:  parts[5],
			}
		}
	}

	// --- Layout Construction ---

	// System Info Column
	appReady := false
	// We'll calculate App Status after iterating services, but we need it for the SysInfo box.
	// Let's do a quick pass or defer rendering.
	// Actually, let's render services first into a buffer/list.

	var accessUrls []string

	// Headers
	// Icon | Service | Image |	// Build Rows (Service Table)
	rows := [][]string{
		{"", headerStyle.Render("SERVICE"), headerStyle.Render("IMAGE"), headerStyle.Render("ID"), headerStyle.Render("STATUS"), headerStyle.Render("PORTS")},
	}

	for _, svc := range definedSvcs {
		svc = strings.TrimSpace(svc)
		if svc == "" {
			continue
		}

		info, exists := svcMap[svc]

		var icon, name, image, id, statusRendered, ports string

		name = svc

		if !exists {
			icon = dotGrey.String()
			image = "-"
			id = "-"
			statusRendered = lipgloss.NewStyle().Foreground(colorGrey).Italic(true).Render("Not Created")
			ports = "-"
		} else {
			// Image: shorten if possible (remove tag if latest? no keep it)
			image = info.Image
			if len(image) > 20 {
				image = "..." + image[len(image)-17:]
			}

			// ID: short
			id = info.ID
			if len(id) > 12 {
				id = id[:12]
			}

			// Ports: clean up
			if strings.Contains(info.Ports, "->") {
				pParts := strings.Split(info.Ports, "->")
				if len(pParts) > 0 {
					raw := pParts[0]
					if idx := strings.LastIndex(raw, ":"); idx != -1 {
						port := raw[idx+1:]
						ports = port

						// URLs
						stateLower := info.State
						isUp := stateLower == "running" && !strings.Contains(strings.ToLower(info.Status), "restarting")
						if isUp {
							if svc == "web" {
								appReady = true
								accessUrls = append(accessUrls, fmt.Sprintf("App: http://localhost:%s", port))
							} else if svc == "phpmyadmin" {
								accessUrls = append(accessUrls, fmt.Sprintf("DB: http://localhost:%s", port))
							} else if svc == "mailpit" {
								accessUrls = append(accessUrls, fmt.Sprintf("Mail: http://localhost:%s", port))
							}
						}
					}
				}
			}
			if ports == "" {
				ports = "-"
			}

			// Status & Icon
			sLower := info.State
			statText := info.Status

			if sLower == "running" {
				if strings.Contains(strings.ToLower(statText), "restarting") {
					icon = dotYellow.String()
					statusRendered = lipgloss.NewStyle().Foreground(colorYellow).Render("Restarting")
				} else {
					icon = dotGreen.String()
					statusRendered = lipgloss.NewStyle().Foreground(colorGreen).Render("Running")
				}
			} else if sLower == "restarting" {
				icon = dotYellow.String()
				statusRendered = lipgloss.NewStyle().Foreground(colorYellow).Render("Restarting")
			} else { // exited, dead, created
				icon = dotRed.String()
				// Use actual status but truncate
				if len(statText) > 25 {
					statText = statText[:22] + "..."
				}
				statusRendered = lipgloss.NewStyle().Foreground(colorRed).Render(statText)
			}
		}

		// Style the columns
		rows = append(rows, []string{
			icon,
			lipgloss.NewStyle().Foreground(colorWhite).Render(name),
			lipgloss.NewStyle().Foreground(colorGrey).Render(image),
			lipgloss.NewStyle().Foreground(colorGrey).Render(id),
			statusRendered,
			lipgloss.NewStyle().Foreground(colorGrey).Render(ports),
		})
	}

	// Calculate widths for nice alignment
	// We will render COLUMN by COLUMN to ensure perfect vertical alignment
	// independent of ANSI codes in rows.

	// 1. Calculate Max Width per Column
	colWidths := make([]int, 6)
	for _, row := range rows {
		for i, col := range row {
			w := lipgloss.Width(col)
			if w > colWidths[i] {
				colWidths[i] = w
			}
		}
	}

	// 2. Build Columns
	var colBlocks []string

	for i := 0; i < 6; i++ {
		// Create a style for this column
		// Add padding to the right for spacing, except last column
		w := colWidths[i]
		if i < 5 {
			w += 2 // Padding between columns
		}

		colStyle := lipgloss.NewStyle().Width(w).Align(lipgloss.Left)

		var cells []string
		for _, row := range rows {
			cells = append(cells, colStyle.Render(row[i]))
		}

		// Join cells vertically to make a column block
		colBlocks = append(colBlocks, lipgloss.JoinVertical(lipgloss.Left, cells...))
	}

	// 3. Join Columns Horizontally
	// This creates the stable grid table
	tableContent := lipgloss.JoinHorizontal(lipgloss.Top, colBlocks...)

	// --- App Status Indicator for System Box ---
	var appStatIcon, appStatMsg string
	if appReady {
		appStatIcon = dotGreen.String()
		appStatMsg = lipgloss.NewStyle().Foreground(colorGreen).Render("Online")
	} else if dockerRunning {
		appStatIcon = dotYellow.String()
		appStatMsg = lipgloss.NewStyle().Foreground(colorYellow).Render("Not Running")
	} else {
		appStatIcon = dotRed.String()
		appStatMsg = lipgloss.NewStyle().Foreground(colorRed).Render("System Down")
	}

	// --- Final Assembly ---

	// Left Box: System Info
	// Manually formatting to look like "gum join"
	sysContent := lipgloss.JoinVertical(lipgloss.Left,
		lipgloss.JoinHorizontal(lipgloss.Left, lipgloss.NewStyle().Foreground(colorGrey).Width(10).Render("ENV"), lipgloss.NewStyle().Foreground(colorWhite).Render(envStr)),
		lipgloss.JoinHorizontal(lipgloss.Left, lipgloss.NewStyle().Foreground(colorGrey).Width(10).Render("VER"), lipgloss.NewStyle().Foreground(colorWhite).Render(gitVer)),
		lipgloss.JoinHorizontal(lipgloss.Left, lipgloss.NewStyle().Foreground(colorGrey).Width(10).Render("APP"), appStatIcon+" "+appStatMsg),
	)
	sysBlock := boxStyle.Render(lipgloss.JoinVertical(lipgloss.Left, headerStyle.Render("SYSTEM"), sysContent))

	// Right Box: Services Table
	tableBlock := boxStyle.Render(lipgloss.JoinVertical(lipgloss.Left, tableContent))

	// Output
	fmt.Println(titleStyle.Render(" PROBENPLANER DASHBOARD "))
	fmt.Println(lipgloss.JoinHorizontal(lipgloss.Top, sysBlock, tableBlock))

	if len(accessUrls) > 0 {
		urlStyle := lipgloss.NewStyle().Foreground(lipgloss.Color("39")).MarginTop(1) // Blue/Cyan
		fmt.Println(urlStyle.Render(strings.Join(accessUrls, "   ")))
	}
	fmt.Println()
}

func init() {
	RootCmd.AddCommand(statusCmd)
}
