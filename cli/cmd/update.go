package cmd

import (
	"fmt"
	"os"
	"os/exec"
	"runtime"
	"strings"

	"github.com/charmbracelet/huh"
	"github.com/charmbracelet/log"
	"github.com/spf13/cobra"
)

var updateCmd = &cobra.Command{
	Use:   "update",
	Short: "Update application to a specific version",

	Run: func(cmd *cobra.Command, args []string) {
		// 1. Fetch
		err := RunSpinner("Fetching updates from remote...", func() error {
			return RunCommandCaptureSilent("git", "fetch", "--tags", "--force")
		})
		if err != nil {
			log.Warn("Fetch failed, using local tags only.", "err", err)
		}

		// 2. Identify Current Version (same format as dashboard)
		currentVer, _ := RunCommandCapture("git", "describe", "--tags", "--always")
		currentVer = strings.TrimSpace(currentVer)

		// 3. Get All Tags with Dates and Subjects
		out, _ := exec.Command("git", "tag", "--sort=-creatordate", "--format=%(refname:short)|%(creatordate:short)|%(subject)").Output()
		lines := strings.Split(strings.TrimSpace(string(out)), "\n")

		if len(lines) == 0 || lines[0] == "" {
			log.Warn("No version tags found.")
			return
		}

		// Build display table AND menu options
		type VersionInfo struct {
			Tag            string
			Date           string
			Subject        string
			MigrationCount int
			IsUpgrade      bool
			IsCurrent      bool
		}

		var versions []VersionInfo

		for _, line := range lines {
			parts := strings.Split(line, "|")
			if len(parts) < 3 {
				continue
			}
			tag := parts[0]
			date := parts[1]
			subject := parts[2]

			// Calculate migration difference
			// Count files in database/migrations that differ between HEAD and this tag
			diffOut, _ := RunCommandCapture("git", "diff", "--name-only", "HEAD", tag, "--", "database/migrations")
			migrationFiles := strings.Split(strings.TrimSpace(diffOut), "\n")
			migrationCount := 0
			for _, f := range migrationFiles {
				if strings.TrimSpace(f) != "" {
					migrationCount++
				}
			}

			// Determine if this is an upgrade or downgrade
			isUpgrade := false
			isCurrent := tag == currentVer

			if !isCurrent {
				// Check if tag is an ancestor of HEAD (meaning we're ahead of it - downgrade)
				// Use RunCommandCaptureSilent which returns error on non-zero exit
				err := RunCommandCaptureSilent("git", "merge-base", "--is-ancestor", tag, "HEAD")
				if err == nil {
					// Tag IS an ancestor of HEAD - we're ahead of this tag (downgrade)
					isUpgrade = false
				} else {
					// Tag is NOT an ancestor of HEAD - it's newer than us (upgrade)
					isUpgrade = true
				}
			}

			versions = append(versions, VersionInfo{
				Tag:            tag,
				Date:           date,
				Subject:        subject,
				MigrationCount: migrationCount,
				IsUpgrade:      isUpgrade,
				IsCurrent:      isCurrent,
			})
		}

		// Filter to only show upgrades (newer versions) and current
		var upgradeVersions []VersionInfo
		for _, v := range versions {
			if v.IsUpgrade || v.IsCurrent {
				upgradeVersions = append(upgradeVersions, v)
			}
		}

		if len(upgradeVersions) <= 1 {
			log.Info("No newer versions available. You're up to date!")
			return
		}

		// Simple display - current version
		log.Info("Current Version:", "version", currentVer)
		fmt.Println()

		// Pause before showing menu so user can see the version info
		fmt.Println("Press Enter to continue...")
		fmt.Scanln()
		fmt.Println()

		// Build menu options (only upgrades)
		var options []huh.Option[string]

		for _, v := range upgradeVersions {
			if v.IsCurrent {
				continue // Skip current in selection
			}

			migrationLabel := ""
			if v.MigrationCount > 0 {
				migrationLabel = fmt.Sprintf(" [%d migrations]", v.MigrationCount)
			}

			label := fmt.Sprintf("%s (%s)%s - %s", v.Tag, v.Date, migrationLabel, v.Subject)
			if len(label) > 80 {
				label = label[:77] + "..."
			}
			options = append(options, huh.NewOption(label, v.Tag))
		}

		// Add cancel
		options = append(options, huh.NewOption("❌ Cancel", ""))

		// 5. Select
		var selectedTag string
		form := huh.NewForm(
			huh.NewGroup(
				huh.NewSelect[string]().
					Title("Select Version to Deploy").
					Options(options...).
					Value(&selectedTag).
					Height(15),
			),
		)

		err = form.Run()
		if err != nil || selectedTag == "" {
			log.Info("Update cancelled.")
			return
		}

		// Find selected version info
		var selectedVersion *VersionInfo
		for i := range versions {
			if versions[i].Tag == selectedTag {
				selectedVersion = &versions[i]
				break
			}
		}

		// 6. Confirm Action with Migration Warning
		var actionType string
		if selectedTag == currentVer {
			actionType = "Reinstall"
		} else if selectedVersion != nil && selectedVersion.IsUpgrade {
			actionType = "Upgrade"
		} else {
			actionType = "Downgrade"
		}

		log.Info(fmt.Sprintf("Ready to %s to %s", actionType, selectedTag))

		// Enhanced migration warning
		if selectedVersion != nil && selectedVersion.MigrationCount > 0 {
			if selectedVersion.IsUpgrade {
				log.Info(fmt.Sprintf("✓ %d new migrations will be applied automatically after deployment.", selectedVersion.MigrationCount))
			} else {
				log.Warn(fmt.Sprintf("⚠️  DOWNGRADE DETECTED: %d migrations need to be rolled back!", selectedVersion.MigrationCount))
				log.Warn("You MUST manually rollback migrations BEFORE proceeding:")
				log.Warn("  1. Run: probenplaner migrate:rollback (multiple times if needed)")
				log.Warn("  2. Or manually revert database changes")
				log.Warn("Proceeding without rollback will cause database/code mismatch!")
			}
		}

		var confirm bool
		huh.NewConfirm().
			Title(fmt.Sprintf("Proceed to checkout %s and rebuild?", selectedTag)).
			Value(&confirm).
			Run()

		if !confirm {
			log.Info("Cancelled.")
			return
		}

		// Create backup before updating
		log.Info("Creating safety backup before update...")
		backupCreateCmd.Run(cmd, args)
		fmt.Println()

		// 7. Checkout
		err = RunSpinner(fmt.Sprintf("Checking out %s...", selectedTag), func() error {
			return RunCommandCaptureSilent("git", "checkout", selectedTag)
		})
		if err != nil {
			log.Fatal("Checkout failed:", err)
		}

		// 8. Rebuild CLI
		err = RunSpinner("Rebuilding CLI...", func() error {
			exe, _ := os.Executable()
			if runtime.GOOS == "windows" {
				oldExe := exe + ".old"
				os.Remove(oldExe)
				os.Rename(exe, oldExe)
			}

			buildScript := "cli/build.sh"
			if runtime.GOOS == "windows" {
				buildScript = "cli\\build.bat"
			}
			return RunCommandCaptureSilent(buildScript)
		})

		if err != nil {
			log.Error("Build failed:", err)
			log.Info("You might differ from the remote version logic. Try running build manually.")
		} else {
			log.Info("CLI Rebuilt successfully.")
		}

		// 9. Restart Services
		log.Info("Restarting Environment...")
		env := GetEnv()
		composeFiles := GetComposeFile(env)

		upArgs := append(append([]string{"compose"}, composeFiles...), "up", "-d", "--build", "--remove-orphans")
		err = RunSpinner("Restarting containers...", func() error {
			return RunCommandCaptureSilent("docker", upArgs...)
		})

		if err != nil {
			log.Error("Failed to restart containers:", err)
		}

		// Migration reminder
		if selectedVersion != nil && selectedVersion.MigrationCount > 0 && selectedVersion.IsUpgrade {
			log.Info("✓ Containers restarted. Don't forget to run migrations:")
			log.Info("   probenplaner migrate up")
		}

		log.Info("Update/Switch complete.")
		if runtime.GOOS == "windows" {
			log.Info("Please restart the CLI to use the new binary.")
			os.Exit(0)
		}
	},
}

func init() {
	RootCmd.AddCommand(updateCmd)
}
