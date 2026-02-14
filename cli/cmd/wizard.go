package cmd

import (
	"fmt"
	"io"
	"os"
	"strings"

	"github.com/charmbracelet/huh"
	"github.com/charmbracelet/log"
	"github.com/joho/godotenv"
)

func RunConfigWizard(missing []string) {
	log.Warn("Configuration is incomplete or missing.", "missing_keys", len(missing))

	// FIRST: If .env doesn't exist at all, try copying .env.example
	if _, err := os.Stat(".env"); os.IsNotExist(err) {
		if _, exampleErr := os.Stat(".env.example"); exampleErr == nil {
			log.Info("Copying .env.example to .env as starting template...")

			// Copy file
			src, err := os.Open(".env.example")
			if err != nil {
				log.Warn("Failed to read .env.example", "err", err)
			} else {
				defer src.Close()

				dst, err := os.Create(".env")
				if err != nil {
					log.Warn("Failed to create .env", "err", err)
				} else {
					defer dst.Close()
					_, err = io.Copy(dst, src)
					if err != nil {
						log.Warn("Failed to copy .env.example", "err", err)
					} else {
						log.Info("✓ Created .env from .env.example")

						// Re-validate to see if we still have missing keys
						newMissing, _ := ValidateEnv()
						if len(newMissing) == 0 {
							log.Info("All required configuration is now present!")
							return
						}
						missing = newMissing
						log.Info("Some values still need attention", "missing", len(missing))
					}
				}
			}
		}
	}

	log.Info("Creating/updating .env configuration...")

	// Load existing (if any) to preserve other values
	envMap, _ := godotenv.Read(".env")
	if envMap == nil {
		envMap = make(map[string]string)
	}

	// Construct fields slice
	var fields []huh.Field
	values := make(map[string]*string)

	for _, key := range missing {
		key := key // capture loop var
		val := ""

		// Set Smart Defaults
		switch key {
		case "APP_ENV":
			val = "development"
		case "APP_URL":
			val = "http://localhost:8080"
		case "DB_CONNECTION":
			val = "mysql"
		case "DB_HOST":
			val = "db"
		case "DB_PORT":
			val = "3306"
		case "DB_USERNAME", "MYSQL_USER":
			val = "probenplaner"
		case "DB_DATABASE", "MYSQL_DATABASE":
			val = "probenplaner"
		}

		values[key] = &val

		input := huh.NewInput().
			Title(fmt.Sprintf("Enter value for %s", key)).
			Value(values[key])

		// Passwords masking
		if strings.Contains(key, "PASSWORD") {
			input.EchoMode(huh.EchoModePassword)
		}

		fields = append(fields, input)
	}

	form := huh.NewForm(huh.NewGroup(fields...))
	if err := form.Run(); err != nil {
		log.Error("Wizard cancelled")
		return
	}

	// Apply values back to map
	for k, v := range values {
		envMap[k] = *v

		// Sync DB_PASSWORD and MYSQL_PASSWORD if one is entered and they usually match
		if k == "DB_PASSWORD" && envMap["MYSQL_PASSWORD"] == "" {
			envMap["MYSQL_PASSWORD"] = *v
		}
		if k == "MYSQL_PASSWORD" && envMap["DB_PASSWORD"] == "" {
			envMap["DB_PASSWORD"] = *v
		}
	}

	// Write .env
	err := godotenv.Write(envMap, ".env")
	if err != nil {
		log.Fatal("Failed to write .env file:", err)
	}

	log.Info("Configuration saved to .env!")
}
