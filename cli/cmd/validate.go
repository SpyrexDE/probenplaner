package cmd

import (
	"fmt"
	"os"

	"github.com/joho/godotenv"
)

var requiredKeys = []string{
	"APP_URL",
	"APP_ENV",
	"DB_CONNECTION",
	"DB_HOST",
	"DB_PORT",
	"DB_DATABASE",
	"DB_USERNAME",
	"DB_PASSWORD",
	"MYSQL_ROOT_PASSWORD",
}

// ValidateEnv checks if .env exists and has critical keys
// Returns a list of missing keys or error if file missing
func ValidateEnv() ([]string, error) {
	if _, err := os.Stat(".env"); os.IsNotExist(err) {
		return requiredKeys, fmt.Errorf(".env file is missing")
	}

	envMap, err := godotenv.Read(".env")
	if err != nil {
		return nil, err
	}

	var missing []string
	for _, key := range requiredKeys {
		if val, ok := envMap[key]; !ok || val == "" {
			missing = append(missing, key)
		}
	}

	return missing, nil
}
