package cmd

import (
	"github.com/charmbracelet/log"
	"github.com/spf13/cobra"
)

var migrateCmd = &cobra.Command{
	Use:   "migrate",
	Short: "Manage database migrations",
}

var migrateStatusCmd = &cobra.Command{
	Use:   "status",
	Short: "Show migration status",
	Run: func(cmd *cobra.Command, args []string) {
		runMigrate("status", args...)
	},
}

var migrateUpCmd = &cobra.Command{
	Use:   "up",
	Short: "Run pending migrations",
	Run: func(cmd *cobra.Command, args []string) {
		runMigrate("up", args...)
	},
}

var migrateCreateCmd = &cobra.Command{
	Use:   "create [name]",
	Short: "Create a new migration",
	Args:  cobra.MinimumNArgs(1),
	Run: func(cmd *cobra.Command, args []string) {
		// args[0] is name
		runMigrate("create", args...)
	},
}

func runMigrate(action string, args ...string) {
	CheckDocker()

	env := GetEnv()
	composeFiles := GetComposeFile(env)

	// Build docker compose exec command
	// docker compose -f ... exec web php /var/www/html/database/cli-migrate.php [action]
	cmdArgs := append(append([]string{"compose"}, composeFiles...), "exec", "web", "php", "/var/www/html/database/cli-migrate.php", action)
	cmdArgs = append(cmdArgs, args...)

	log.Info("Running migration command...", "action", action)
	if err := RunCommand("docker", cmdArgs...); err != nil {
		log.Error("Migration command failed", "err", err)
	} else {
		if action == "up" {
			log.Info("Migrations completed successfully.")
		} else if action == "create" {
			log.Info("Migration created successfully.")
		}
	}
}

func init() {
	migrateCmd.AddCommand(migrateStatusCmd)
	migrateCmd.AddCommand(migrateUpCmd)
	migrateCmd.AddCommand(migrateCreateCmd)
	RootCmd.AddCommand(migrateCmd)
}
