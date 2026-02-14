package cmd

import (
	"strings"

	"github.com/charmbracelet/huh"
	"github.com/charmbracelet/log"
	"github.com/spf13/cobra"
)

var dbCmd = &cobra.Command{
	Use:   "db",
	Short: "Database utility commands",
}

var dbResetCmd = &cobra.Command{
	Use:   "reset",
	Short: "Dangerous: Reset database (delete all data)",
	Run: func(cmd *cobra.Command, args []string) {
		CheckDocker()
		env := GetEnv()

		log.Warn("You are about to RESET the database for environment:", "env", env)
		log.Warn("This will DELETE ALL DATA in the database volume.")

		// 1. Simple Confirmation
		var confirm bool
		form := huh.NewForm(
			huh.NewGroup(
				huh.NewConfirm().
					Title("Are you sure you want to proceed?").
					Value(&confirm),
			),
		)
		form.Run()

		if !confirm {
			log.Info("Operation cancelled.")
			return
		}

		// 2. Hard Confirmation (Type "RESET")
		var input string
		inputForm := huh.NewForm(
			huh.NewGroup(
				huh.NewInput().
					Title("Type 'RESET' to confirm").
					Value(&input),
			),
		)
		inputForm.Run()

		if strings.TrimSpace(input) != "RESET" {
			log.Info("Confirmation failed. Operation cancelled.")
			return
		}

		log.Info("Resetting database...", "env", env)

		// Logic:
		// 1. Stop containers
		// 2. Remove volume (down -v)
		// 3. Start containers again? Or leave stopped?
		// "Reset" usually implies wipe and restart to fresh state.

		composeFiles := GetComposeFile(env)

		// Stop and remove volumes
		downArgs := append(append([]string{"compose"}, composeFiles...), "down", "-v", "--remove-orphans")
		if err := RunCommand("docker", downArgs...); err != nil {
			log.Fatal("Failed to remove volumes:", err)
		}

		// Restart?
		// Check if user wants to restart
		var restart bool
		restartForm := huh.NewForm(
			huh.NewGroup(
				huh.NewConfirm().
					Title("Database reset. Start environment now?").
					Value(&restart),
			),
		)
		restartForm.Run()

		if restart {
			upArgs := append(append([]string{"compose"}, composeFiles...), "up", "-d")
			if err := RunCommand("docker", upArgs...); err != nil {
				log.Fatal("Failed to start environment:", err)
			}
			log.Info("Environment started with fresh database.")
		} else {
			log.Info("Database reset complete. Environment is stopped.")
		}
	},
}

func init() {
	dbCmd.AddCommand(dbResetCmd)
	RootCmd.AddCommand(dbCmd)
}
