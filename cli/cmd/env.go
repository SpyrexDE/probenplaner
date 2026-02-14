package cmd

import (
	"fmt"
	"os"
	"strings"

	"github.com/charmbracelet/huh"
	"github.com/charmbracelet/log"
	"github.com/spf13/cobra"
)

var envCmd = &cobra.Command{
	Use:   "env",
	Short: "Manage application environment",
	Run: func(cmd *cobra.Command, args []string) {
		currentEnv := GetEnv()
		log.Info("Current Environment", "mode", currentEnv)

		var newEnv string
		form := huh.NewForm(
			huh.NewGroup(
				huh.NewSelect[string]().
					Title("Switch Environment").
					Options(
						huh.NewOption("Development", EnvDev),
						huh.NewOption("Production", EnvProd),
						huh.NewOption("Test", EnvTest),
					).
					Value(&newEnv),
			),
		)

		if err := form.Run(); err != nil {
			log.Info("Selection cancelled")
			return
		}

		if newEnv != currentEnv {
			err := os.WriteFile(envFileMode, []byte(newEnv), 0644)
			if err != nil {
				log.Fatal("Failed to save environment mode:", err)
			}

			// Also sync APP_ENV in .env if it exists
			// dev -> development, prod -> production, test -> test
			appEnv := "development"
			if newEnv == EnvProd {
				appEnv = "production"
			}
			if newEnv == EnvTest {
				appEnv = "test"
			}

			// We need to read/write .env carefully to not nuke other comments/structure
			// But godotenv/custom parser is needed.
			// For now, let's use the wizard's logic or simple replace if we want to be safe?
			// Actually, godotenv.Write overwrites everything and removes comments. That's BAD.
			// Let's just log a reminder for now, or use sed-like replacement?
			// The user complained "switching to prod doesnt change the app env".
			// Let's try to update it if we can.
			// "sed" replacement is safer for comments.
			updateEnvFile("APP_ENV", appEnv)

			log.Info("Environment switched successfully!", "from", currentEnv, "to", newEnv)
		} else {
			log.Info("Environment unchanged.")
		}
	},
}

func init() {
	RootCmd.AddCommand(envCmd)
}

// Simple helper to update a single key in .env line-by-line to preserve comments
func updateEnvFile(key, value string) {
	content, err := os.ReadFile(".env")
	if err != nil {
		return
	} // If no .env, ignore

	lines := strings.Split(string(content), "\n")
	found := false
	for i, line := range lines {
		if strings.HasPrefix(strings.TrimSpace(line), key+"=") {
			lines[i] = fmt.Sprintf("%s=\"%s\"", key, value)
			found = true
			break
		}
	}

	if !found {
		// Append if not found
		lines = append(lines, fmt.Sprintf("%s=\"%s\"", key, value))
	}

	os.WriteFile(".env", []byte(strings.Join(lines, "\n")), 0644)
}
