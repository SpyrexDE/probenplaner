package cmd

import (
	"fmt"
	"net"
	"os"
	"os/exec"
	"strings"
	"time"

	"github.com/charmbracelet/lipgloss"
	"github.com/spf13/cobra"
)

var doctorCmd = &cobra.Command{
	Use:   "doctor",
	Short: "Check system health and prerequisites",

	Run: func(cmd *cobra.Command, args []string) {
		// --- Gum Style Colors ---
		colorPink := lipgloss.Color("212")
		colorPurple := lipgloss.Color("57")
		colorWhite := lipgloss.Color("255")
		colorGrey := lipgloss.Color("240")
		colorGreen := lipgloss.Color("46")
		colorRed := lipgloss.Color("196")
		colorYellow := lipgloss.Color("226")

		// Styles
		titleStyle := lipgloss.NewStyle().
			Foreground(colorWhite).
			Background(colorPurple).
			Padding(0, 1).
			Bold(true)

		boxStyle := lipgloss.NewStyle().
			Border(lipgloss.DoubleBorder()).
			BorderForeground(colorPink).
			Padding(0, 1)

		headerStyle := lipgloss.NewStyle().
			Foreground(colorPink).
			Bold(true)

		// Check results
		type CheckResult struct {
			Name    string
			Status  string // "ok", "fail", "warn"
			Message string
		}

		var results []CheckResult

		check := func(name string, fn func() error) {
			if err := fn(); err != nil {
				results = append(results, CheckResult{
					Name:    name,
					Status:  "fail",
					Message: err.Error(),
				})
			} else {
				results = append(results, CheckResult{
					Name:    name,
					Status:  "ok",
					Message: "Ready",
				})
			}
		}

		// Run Checks
		check(".env Configuration", func() error {
			missing, err := ValidateEnv()
			if os.IsNotExist(err) {
				return fmt.Errorf(".env file is missing")
			}
			if len(missing) > 0 {
				return fmt.Errorf("missing keys: %s", strings.Join(missing, ", "))
			}
			return nil
		})

		check("Docker Daemon", func() error {
			return exec.Command("docker", "info").Run()
		})

		check("Docker Compose V2+", func() error {
			out, err := exec.Command("docker", "compose", "version").CombinedOutput()
			if err != nil {
				return err
			}
			// Accept v2, v3, v4, v5, etc.
			version := strings.TrimSpace(string(out))
			if !strings.Contains(version, "Docker Compose version") {
				return fmt.Errorf("unexpected version format: %s", version)
			}
			return nil
		})

		check("Port 8080 (Web)", func() error {
			conn, err := net.DialTimeout("tcp", "localhost:8080", 1*time.Second)
			if err == nil {
				conn.Close()
				return nil
			}
			return fmt.Errorf("nothing listening (App might be down)")
		})

		check("Git Repository", func() error {
			return exec.Command("git", "status").Run()
		})

		check("Database Connection", func() error {
			env := GetEnv()
			composeFiles := GetComposeFile(env)

			// Use docker compose exec to ping database
			pingArgs := append(append([]string{"compose"}, composeFiles...), "exec", "-T", "db", "mysqladmin", "ping", "-h", "localhost", "--silent")
			return exec.Command("docker", pingArgs...).Run()
		})

		// Build Table
		rows := [][]string{
			{"", headerStyle.Render("CHECK"), headerStyle.Render("STATUS"), headerStyle.Render("MESSAGE")},
		}

		for _, r := range results {
			var icon, statusCell, msgCell string

			switch r.Status {
			case "ok":
				icon = lipgloss.NewStyle().Foreground(colorGreen).Render("✓")
				statusCell = lipgloss.NewStyle().Foreground(colorGreen).Bold(true).Render("OK")
				msgCell = lipgloss.NewStyle().Foreground(colorGrey).Render(r.Message)
			case "fail":
				icon = lipgloss.NewStyle().Foreground(colorRed).Render("✖")
				statusCell = lipgloss.NewStyle().Foreground(colorRed).Bold(true).Render("FAIL")
				msgCell = lipgloss.NewStyle().Foreground(colorRed).Render(r.Message)
			case "warn":
				icon = lipgloss.NewStyle().Foreground(colorYellow).Render("⚠")
				statusCell = lipgloss.NewStyle().Foreground(colorYellow).Bold(true).Render("WARN")
				msgCell = lipgloss.NewStyle().Foreground(colorYellow).Render(r.Message)
			}

			nameCell := lipgloss.NewStyle().Foreground(colorWhite).Render(r.Name)
			rows = append(rows, []string{icon, nameCell, statusCell, msgCell})
		}

		// Calculate column widths
		colWidths := make([]int, 4)
		for _, row := range rows {
			for i, col := range row {
				w := lipgloss.Width(col)
				if w > colWidths[i] {
					colWidths[i] = w
				}
			}
		}

		// Build columns
		var colBlocks []string
		for i := 0; i < 4; i++ {
			w := colWidths[i]
			if i < 3 {
				w += 2 // Padding
			}

			colStyle := lipgloss.NewStyle().Width(w).Align(lipgloss.Left)

			var cells []string
			for _, row := range rows {
				cells = append(cells, colStyle.Render(row[i]))
			}

			colBlocks = append(colBlocks, lipgloss.JoinVertical(lipgloss.Left, cells...))
		}

		tableContent := lipgloss.JoinHorizontal(lipgloss.Top, colBlocks...)

		// Output
		fmt.Println(titleStyle.Render(" SYSTEM HEALTH CHECK "))
		fmt.Println(boxStyle.Render(tableContent))
		fmt.Println()
	},
}

func init() {
	RootCmd.AddCommand(doctorCmd)
}
