package cmd

import (
	"fmt"
	"os"
	"os/exec"
	"strings"

	"github.com/charmbracelet/huh"
	"github.com/charmbracelet/log"
	"github.com/spf13/cobra"
)

const (
	EnvDev  = "dev"
	EnvProd = "prod"
	EnvTest = "test"
)

var (
	envFileMode = ".env.mode"
)

// RootCmd represents the base command when called without any subcommands
var RootCmd = &cobra.Command{
	Use:   "probenplaner",
	Short: "Probenplaner CLI Tool",
	Long: `A professional command-line interface for managing the Probenplaner application
across development, test, and production environments.`,
	Run: func(cmd *cobra.Command, args []string) {
		// Main Interactive Loop
		for {
			// 1. Clear Screen
			fmt.Print("\033[H\033[2J")

			// 2. Show Status
			printStatus()

			// 2b. Check Config
			// Strict check: Only run wizard if .env is missing
			if _, statErr := os.Stat(".env"); os.IsNotExist(statErr) {
				// File does NOT exist -> Run Wizard
				missing, _ := ValidateEnv() // Returns all keys if file missing
				RunConfigWizard(missing)
				printStatus()
			} else {

				// File exists -> Check for missing keys but DO NOT run wizard
				missing, _ := ValidateEnv()
				if len(missing) > 0 {
					log.Warn("Configuration incomplete", "missing", strings.Join(missing, ", "))
				}
			}

			// 3. Menu
			action, err := RunMenu()
			if err != nil {
				log.Error("Menu error:", err)
				return
			}
			if action == "" {
				return // User quit or ctrl+c
			}

			// 4. Handle Action
			switch action {
			case "up":
				startEnv("")
				pause()

			case "down":
				downCmd.Run(downCmd, []string{})
				pause()

			case "shell":
				shellCmd.Run(shellCmd, []string{})

			case "logs":
				logsCmd.Run(logsCmd, []string{"-f"})

			case "migrate":
				// Sub-menu for Migrations
				var migAction string
				huh.NewSelect[string]().
					Title("Migration Actions").
					Options(
						huh.NewOption("🔎 Status", "status"),
						huh.NewOption("🚀 Run Migrations (Up)", "up"),
						huh.NewOption("✨ Create New Migration", "create"),
						huh.NewOption("⬅️  Back", "back"),
					).
					Value(&migAction).Run()

				switch migAction {
				case "status":
					migrateStatusCmd.Run(migrateStatusCmd, []string{})
					pause()
				case "up":
					migrateUpCmd.Run(migrateUpCmd, []string{})
					pause()
				case "create":
					var name string
					huh.NewInput().Title("Migration Name").Value(&name).Run()
					if name != "" {
						migrateCreateCmd.Run(migrateCreateCmd, []string{name})
						pause()
					}
				}

			case "backup":
				// Sub-menu for Backup implies List/Restore/Create?
				// The backup command in root just called backupListCmd before.
				// Let's make it a sub-menu too since we have create/list/restore.
				var bkAction string
				huh.NewSelect[string]().
					Title("Backup Actions").
					Options(
						huh.NewOption("📄 List Backups", "list"),
						huh.NewOption("💾 Create Backup", "create"),
						huh.NewOption("♻️  Restore Backup", "restore"),
						huh.NewOption("⬅️  Back", "back"),
					).
					Value(&bkAction).Run()

				switch bkAction {
				case "list":
					backupListCmd.Run(backupListCmd, []string{})
					pause()
				case "create":
					backupCreateCmd.Run(backupCreateCmd, []string{})
					pause()
				case "restore":
					backupRestoreCmd.Run(backupRestoreCmd, []string{})
					pause()
				}

			case "clean":
				cleanCmd.Run(cleanCmd, []string{})
				pause()

			case "build":
				buildCmd.Run(buildCmd, []string{})
				pause()

			case "doctor":
				doctorCmd.Run(doctorCmd, []string{})
				pause()

			case "update":
				updateCmd.Run(updateCmd, []string{})

			case "open":
				openCmd.Run(openCmd, []string{})

			case "switch":
				envCmd.Run(envCmd, []string{})
				// No pause needed, dashboard updates immediately

			case "exit":
				os.Exit(0)
			}
		}
	},
}

func pause() {
	var dummy string
	huh.NewForm(
		huh.NewGroup(
			huh.NewInput().Title("Press Enter to continue...").Value(&dummy),
		),
	).Run()
}

// Execute adds all child commands to the root command and sets flags appropriately.
func Execute() {
	if err := RootCmd.Execute(); err != nil {
		os.Exit(1)
	}
}

func init() {
	// Initialize logging
	log.SetLevel(log.InfoLevel)
	log.SetTimeFormat("15:04:05")

	// Ensure we are in project root or have access to it
	if _, err := os.Stat("docker-compose.yml"); os.IsNotExist(err) {
		log.Warn("docker-compose.yml not found in current directory. Make sure you are in the project root.")
	}

	// Auto-inject GIT_VERSION so docker-compose receives the correct tag
	if os.Getenv("GIT_VERSION") == "" {
		out, err := exec.Command("git", "describe", "--tags", "--always").Output()
		if err == nil {
			os.Setenv("GIT_VERSION", strings.TrimSpace(string(out)))
		} else {
			os.Setenv("GIT_VERSION", "N/A")
		}
	}
}

// GetEnv returns the current environment mode from .env.mode file
// If file doesn't exist, it checks .env for APP_ENV, or asks the user.
func GetEnv() string {
	// 1. Try reading .env.mode
	content, err := os.ReadFile(envFileMode)
	if err == nil {
		env := strings.TrimSpace(string(content))
		if env == EnvDev || env == EnvProd || env == EnvTest {
			return env
		}
	}

	// 2. Try sniffing .env for APP_ENV
	if envBytes, err := os.ReadFile(".env"); err == nil {
		envContent := string(envBytes)
		lines := strings.Split(envContent, "\n")
		for _, line := range lines {
			if strings.HasPrefix(strings.TrimSpace(line), "APP_ENV=") {
				parts := strings.SplitN(line, "=", 2)
				if len(parts) == 2 {
					val := strings.ToLower(strings.TrimSpace(strings.Trim(parts[1], `"'`)))
					var mode string

					// Map full names to modes
					switch val {
					case "production", "prod":
						mode = EnvProd
					case "testing", "test":
						mode = EnvTest
					case "development", "dev":
						mode = EnvDev
					}

					if mode != "" {
						// Found it! Save and return
						_ = os.WriteFile(envFileMode, []byte(mode), 0644)
						return mode
					}
				}
			}
		}
	}

	// 3. Env not set or invalid, ask user
	var env string
	form := huh.NewForm(
		huh.NewGroup(
			huh.NewSelect[string]().
				Title("Select Environment Mode").
				Options(
					huh.NewOption("Development", EnvDev),
					huh.NewOption("Production", EnvProd),
					huh.NewOption("Test", EnvTest),
				).
				Value(&env),
		),
	)

	err = form.Run()
	if err != nil {
		log.Fatal("Failed to select environment:", err)
	}

	// Save to file
	err = os.WriteFile(envFileMode, []byte(env), 0644)
	if err != nil {
		log.Fatal("Failed to save environment mode:", err)
	}

	log.Info("Environment set", "mode", env)
	return env
}

// GetComposeFile returns the docker-compose file for the current env.
// Each env file is standalone — never merged with the base to avoid
// dev-only config (e.g. ports, phpmyadmin) leaking into prod/test.
func GetComposeFile(env string) []string {
	switch env {
	case EnvProd:
		return []string{"-f", "docker-compose.prod.yml"}
	case EnvTest:
		return []string{"-f", "docker-compose.test.yml"}
	default:
		return []string{"-f", "docker-compose.yml"}
	}
}
