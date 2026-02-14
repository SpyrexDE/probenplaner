package cmd

import (
	"compress/gzip"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"sort"
	"strings"
	"time"

	"github.com/charmbracelet/huh"
	"github.com/charmbracelet/lipgloss"
	"github.com/charmbracelet/log"
	"github.com/spf13/cobra"
)

var backupCmd = &cobra.Command{
	Use:   "backup",
	Short: "Manage database backups",
}

var backupCreateCmd = &cobra.Command{
	Use:   "create",
	Short: "Create a new database backup",
	Run: func(cmd *cobra.Command, args []string) {
		CheckDocker()

		// 1. Get DB Container and Credentials
		// We need to read .env or just use docker exec to run printenv
		// Or simpler: exec mysqldump inside the db container (it usually has env vars set)

		// We need to find the DB container name.
		// Usually 'probenplaner-db-1' or similar.
		// Start by finding web container to identify project prefix?
		// Or just look for container with label?

		env := GetEnv()
		composeFiles := GetComposeFile(env)

		// Get db service container
		log.Info("Identifying database container...")
		// docker compose ps db --format '{{.Name}}'
		psArgs := append(append([]string{"compose"}, composeFiles...), "ps", "db", "--format", "{{.Name}}")
		dbContainer, err := RunCommandCapture("docker", psArgs...)
		if err != nil {
			log.Fatal("Failed to find db container:", err)
		}
		dbContainer = strings.TrimSpace(dbContainer)
		if dbContainer == "" {
			log.Fatal("DB container not running")
		}

		// 2. Prepare Backup Directory
		backupDir := "backups"
		if err := os.MkdirAll(backupDir, 0755); err != nil {
			log.Fatal("Failed to create backup directory:", err)
		}

		// 3. Generate Filename
		// Format: backup_YYYY-MM-DD_HH-mm_v[VERSION].sql.gz
		gitVer, _ := RunCommandCapture("git", "describe", "--tags", "--always")
		gitVer = strings.TrimSpace(gitVer)
		timestamp := time.Now().Format("2006-01-02_15-04")
		filename := fmt.Sprintf("backup_%s_%s.sql.gz", timestamp, gitVer)
		filepath := filepath.Join(backupDir, filename)

		log.Info("Creating backup...", "file", filename)

		// 4. Run mysqldump and pipe to file
		// docker exec container mysqldump ...

		// Setup output file
		outFile, err := os.Create(filepath)
		if err != nil {
			log.Fatal("Failed to create backup file:", err)
		}
		defer outFile.Close()

		// Setup Gzip Writer
		gw := gzip.NewWriter(outFile)
		defer gw.Close()

		// Prepare Docker Compose Command
		composeArgs := append(append([]string{"compose"}, composeFiles...), "exec", "-T", "db", "sh", "-c",
			"mysqldump -u \"$MYSQL_USER\" -p\"$MYSQL_PASSWORD\" \"$MYSQL_DATABASE\"")
		dumpCmd := exec.Command("docker", composeArgs...)

		// Pipe stdout to gzip writer
		dumpCmd.Stdout = gw
		dumpCmd.Stderr = os.Stderr

		err = RunSpinner("Streaming database dump to file...", func() error {
			if err := dumpCmd.Run(); err != nil {
				return err
			}
			return gw.Close() // Close gzip inside to ensure it flushes
		})

		if err != nil {
			log.Fatal("Backup failed:", err)
		}

		info, _ := os.Stat(filepath)

		// Gum-style success message
		colorPink := lipgloss.Color("212")
		colorWhite := lipgloss.Color("255")
		colorGreen := lipgloss.Color("46")

		titleStyle := lipgloss.NewStyle().
			Foreground(colorWhite).
			Background(colorGreen).
			Padding(0, 1).
			Bold(true)

		boxStyle := lipgloss.NewStyle().
			Border(lipgloss.DoubleBorder()).
			BorderForeground(colorPink).
			Padding(1, 2)

		labelStyle := lipgloss.NewStyle().Foreground(colorPink).Bold(true)
		valueStyle := lipgloss.NewStyle().Foreground(colorWhite)

		sizeStr := fmt.Sprintf("%.2f MB", float64(info.Size())/1024/1024)

		details := fmt.Sprintf(
			"%s %s\n%s %s\n%s %s",
			labelStyle.Render("Filename:"),
			valueStyle.Render(filename),
			labelStyle.Render("Size:"),
			valueStyle.Render(sizeStr),
			labelStyle.Render("Location:"),
			valueStyle.Render(filepath),
		)

		fmt.Println()
		fmt.Println(titleStyle.Render(" ✓ BACKUP CREATED SUCCESSFULLY "))
		fmt.Println(boxStyle.Render(details))
		fmt.Println()
	},
}

var backupListCmd = &cobra.Command{
	Use:   "list",
	Short: "List available backups",

	Run: func(cmd *cobra.Command, args []string) {
		files, err := os.ReadDir("backups")
		hasBackups := false
		if err == nil {
			for _, f := range files {
				if !f.IsDir() && strings.HasSuffix(f.Name(), ".sql.gz") {
					hasBackups = true
					break
				}
			}
		}

		if !hasBackups {
			log.Info("No backups found.")
			return
		}

		// --- Gum Style Colors ---
		colorPink := lipgloss.Color("212")
		colorPurple := lipgloss.Color("57")
		colorWhite := lipgloss.Color("255")
		colorGrey := lipgloss.Color("240")
		colorCyan := lipgloss.Color("51")

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

		// Parse backups
		type BackupInfo struct {
			Filename  string
			Timestamp string
			Version   string
			Size      string
			Date      time.Time
		}

		var backups []BackupInfo

		for _, file := range files {
			if !file.IsDir() && strings.HasSuffix(file.Name(), ".sql.gz") {
				info, _ := file.Info()
				size := fmt.Sprintf("%.2f MB", float64(info.Size())/1024/1024)

				// Extract timestamp and version from filename
				// Format: backup_2026-02-14_18-45_v1.2.3.sql.gz
				name := file.Name()
				version := "unknown"
				timestamp := info.ModTime().Format("2006-01-02 15:04")

				// Try to extract version from filename
				if idx := strings.LastIndex(name, "_v"); idx != -1 {
					versionPart := name[idx+2:] // Skip "_v"
					if dotIdx := strings.Index(versionPart, ".sql"); dotIdx != -1 {
						version = versionPart[:dotIdx]
					}
				}

				// Try to extract timestamp from filename
				// backup_YYYY-MM-DD_HH-mm_...
				parts := strings.Split(name, "_")
				if len(parts) >= 3 {
					dateStr := parts[1]                                // YYYY-MM-DD
					timeStr := strings.Replace(parts[2], "-", ":", -1) // HH-mm -> HH:mm
					timestamp = dateStr + " " + timeStr
				}

				backups = append(backups, BackupInfo{
					Filename:  name,
					Timestamp: timestamp,
					Version:   version,
					Size:      size,
					Date:      info.ModTime(),
				})
			}
		}

		// Sort by date descending
		sort.Slice(backups, func(i, j int) bool {
			return backups[i].Date.After(backups[j].Date)
		})

		// Build table with columns: Icon | Timestamp | Version | Size | Filename
		rows := [][]string{
			{"", headerStyle.Render("CREATED"), headerStyle.Render("VERSION"), headerStyle.Render("SIZE"), headerStyle.Render("FILENAME")},
		}

		for _, b := range backups {
			icon := lipgloss.NewStyle().Foreground(colorCyan).Render("📦")
			timestampCell := lipgloss.NewStyle().Foreground(colorWhite).Render(b.Timestamp)
			versionCell := lipgloss.NewStyle().Foreground(colorPink).Render(b.Version)
			sizeCell := lipgloss.NewStyle().Foreground(colorGrey).Render(b.Size)
			filenameCell := lipgloss.NewStyle().Foreground(colorGrey).Render(b.Filename)

			rows = append(rows, []string{icon, timestampCell, versionCell, sizeCell, filenameCell})
		}

		// Calculate column widths
		colWidths := make([]int, 5)
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
		for i := 0; i < 5; i++ {
			w := colWidths[i]
			if i < 4 {
				w += 2 // Padding between columns
			}

			colStyle := lipgloss.NewStyle().Width(w).Align(lipgloss.Left)

			var cells []string
			for _, row := range rows {
				cells = append(cells, colStyle.Render(row[i]))
			}

			colBlocks = append(colBlocks, lipgloss.JoinVertical(lipgloss.Left, cells...))
		}

		// Join columns horizontally
		tableContent := lipgloss.JoinHorizontal(lipgloss.Top, colBlocks...)

		// Final output
		fmt.Println(titleStyle.Render(" DATABASE BACKUPS "))
		fmt.Println(boxStyle.Render(tableContent))
		fmt.Println()
	},
}

var backupRestoreCmd = &cobra.Command{
	Use:   "restore",
	Short: "Restore database from backup",
	Run: func(cmd *cobra.Command, args []string) {
		CheckDocker()

		// 1. Select Backup
		files, err := os.ReadDir("backups")
		if err != nil || len(files) == 0 {
			log.Fatal("No backups found to restore.")
		}

		// Sort by time desc
		var fileNames []string
		for _, f := range files {
			if strings.HasSuffix(f.Name(), ".sql.gz") {
				fileNames = append(fileNames, f.Name())
			}
		}
		// Basic reverse sort (assuming name contains date helps, but modification time is better)
		// For simplicity, just reverse string sort (since date is YYYY-MM-DD...)
		sort.Sort(sort.Reverse(sort.StringSlice(fileNames)))

		var selectedBackup string

		// Get current version for comparison
		currentVer, _ := RunCommandCapture("git", "describe", "--tags", "--always")
		currentVer = strings.TrimSpace(currentVer)

		// Interactive Selection with version info
		options := make([]huh.Option[string], len(fileNames))
		for i, name := range fileNames {
			// Extract version from filename
			backupVersion := ""
			if idx := strings.Index(name, "_v"); idx != -1 {
				versionPart := name[idx+2:]
				if endIdx := strings.Index(versionPart, ".sql.gz"); endIdx != -1 {
					backupVersion = versionPart[:endIdx]
				}
			}

			// Calculate commit distance
			distanceInfo := ""
			if backupVersion != "" && backupVersion != currentVer {
				// Count commits between versions
				// git rev-list --count backupVersion..currentVer (how many commits ahead we are)
				commitsAhead, _ := RunCommandCapture("git", "rev-list", "--count", backupVersion+".."+currentVer)
				commitsAhead = strings.TrimSpace(commitsAhead)

				// git rev-list --count currentVer..backupVersion (how many commits behind we are)
				commitsBehind, _ := RunCommandCapture("git", "rev-list", "--count", currentVer+".."+backupVersion)
				commitsBehind = strings.TrimSpace(commitsBehind)

				if commitsAhead != "0" && commitsAhead != "" {
					distanceInfo = fmt.Sprintf(" (%s commits behind)", commitsAhead)
				} else if commitsBehind != "0" && commitsBehind != "" {
					distanceInfo = fmt.Sprintf(" (%s commits ahead)", commitsBehind)
				}
			} else if backupVersion == currentVer {
				distanceInfo = " (current version)"
			}

			displayName := name + distanceInfo
			options[i] = huh.NewOption[string](displayName, name)
		}

		form := huh.NewForm(
			huh.NewGroup(
				huh.NewSelect[string]().
					Title("Select Backup to Restore").
					Options(options...).
					Value(&selectedBackup),
			),
		)

		if err := form.Run(); err != nil {
			log.Fatal("Selection cancelled")
		}

		// 2. Confirmation
		var confirm bool
		confirmForm := huh.NewForm(
			huh.NewGroup(
				huh.NewConfirm().
					Title("⚠️  DANGER: This will OVERWRITE the current database!").
					Description("Are you absolutely sure?").
					Value(&confirm),
			),
		)

		confirmForm.Run()

		if !confirm {
			log.Info("Restore cancelled.")
			return
		}

		// Extract version from backup filename
		// Format: backup_YYYY-MM-DD_HH-MM_vX.X.X-N-gHASH.sql.gz
		var backupVersion string
		if idx := strings.Index(selectedBackup, "_v"); idx != -1 {
			versionPart := selectedBackup[idx+2:] // After "_v"
			if endIdx := strings.Index(versionPart, ".sql.gz"); endIdx != -1 {
				backupVersion = versionPart[:endIdx]
			}
		}

		// Get current version
		currentVer, _ = RunCommandCapture("git", "describe", "--tags", "--always")
		currentVer = strings.TrimSpace(currentVer)

		// Check version mismatch
		if backupVersion != "" && backupVersion != currentVer {
			log.Warn("⚠️  Version Mismatch Detected!")
			log.Warn("Backup version:", "version", backupVersion)
			log.Warn("Current version:", "version", currentVer)
			fmt.Println()
			log.Warn("Restoring a backup from a different version may cause database/code conflicts.")
			fmt.Println()

			var wantCheckout bool
			checkoutForm := huh.NewForm(
				huh.NewGroup(
					huh.NewConfirm().
						Title(fmt.Sprintf("Checkout version %s before restoring?", backupVersion)).
						Description("This will switch your code to match the backup version.").
						Value(&wantCheckout),
				),
			)
			checkoutForm.Run()

			if wantCheckout {
				log.Info("Checking out version to match backup...")

				// Checkout the version
				err := RunSpinner(fmt.Sprintf("Checking out %s...", backupVersion), func() error {
					return RunCommandCaptureSilent("git", "checkout", backupVersion)
				})
				if err != nil {
					log.Fatal("Checkout failed:", err)
					return
				}

				// Rebuild containers
				log.Info("Rebuilding containers for new version...")
				err = RunSpinner("Rebuilding and restarting containers...", func() error {
					downErr := RunCommandCaptureSilent("docker", "compose", "down")
					if downErr != nil {
						return downErr
					}
					upArgs := append(append([]string{"compose"}, GetComposeFile(GetEnv())...), "up", "-d", "--build")
					return RunCommandCaptureSilent("docker", upArgs...)
				})
				if err != nil {
					log.Warn("Container rebuild had issues:", err)
				}

				log.Info("Version switched successfully!")
				fmt.Println()
			}
		}

		// 3. Perform Restore
		log.Info("Restoring backup...", "file", selectedBackup)

		env := GetEnv()
		composeFiles := GetComposeFile(env)
		psArgs := append(append([]string{"compose"}, composeFiles...), "ps", "db", "--format", "{{.Name}}")
		dbContainer, _ := RunCommandCapture("docker", psArgs...)
		dbContainer = strings.TrimSpace(dbContainer)

		backupPath := filepath.Join("backups", selectedBackup)
		file, err := os.Open(backupPath)
		if err != nil {
			log.Fatal("Failed to open backup file:", err)
		}
		defer file.Close()

		// Gzip Reader
		gr, err := gzip.NewReader(file)
		if err != nil {
			log.Fatal("Failed to create gzip reader:", err)
		}
		defer gr.Close()

		composeArgs := append(append([]string{"compose"}, composeFiles...), "exec", "-T", "-i", "db", "sh", "-c",
			"mysql -u \"$MYSQL_USER\" -p\"$MYSQL_PASSWORD\" \"$MYSQL_DATABASE\"")
		restoreCmd := exec.Command("docker", composeArgs...)

		restoreCmd.Stdin = gr
		restoreCmd.Stdout = os.Stdout
		restoreCmd.Stderr = os.Stderr

		if err := restoreCmd.Run(); err != nil {
			log.Fatal("Restore failed:", err)
		}

		log.Info("Restore completed successfully!")
	},
}

func init() {
	backupCmd.AddCommand(backupCreateCmd)
	backupCmd.AddCommand(backupListCmd)
	backupCmd.AddCommand(backupRestoreCmd)
	RootCmd.AddCommand(backupCmd)
}
