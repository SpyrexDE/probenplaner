package cmd

import (
	"github.com/charmbracelet/huh"
	"github.com/charmbracelet/log"
	"github.com/spf13/cobra"
)

var downCmd = &cobra.Command{
	Use:   "down",
	Short: "Stop all containers",
	Run: func(cmd *cobra.Command, args []string) {
		CheckDocker()
		log.Info("Stopping containers...")

		// We stop ALL potential containers to be safe
		// docker compose down
		RunCommand("docker", "compose", "down")
		// docker compose -f docker-compose.prod.yml down
		RunCommand("docker", "compose", "-f", "docker-compose.prod.yml", "down")
		// docker compose -f docker-compose.test.yml down
		RunCommand("docker", "compose", "-f", "docker-compose.test.yml", "down")

		log.Info("All containers stopped.")
	},
}

var cleanCmd = &cobra.Command{
	Use:   "clean",
	Short: "Remove all containers and volumes",

	Run: func(cmd *cobra.Command, args []string) {
		CheckDocker()

		// SAFETY CHECK
		log.Warn("You are about to REMOVE ALL CONTAINERS AND VOLUMES.")
		log.Warn("This action is irreversible and will delete all data.")

		var confirm string
		huh.NewInput().
			Title("Type 'DELETE EVERYTHING' to confirm").
			Value(&confirm).
			Run()

		if confirm != "DELETE EVERYTHING" {
			log.Info("Operation cancelled. You must type exactly 'DELETE EVERYTHING'.")
			return
		}

		log.Warn("Cleaning up containers and volumes...")

		// Similar to down but with -v --remove-orphans
		cmds := [][]string{
			{"compose", "down", "-v", "--remove-orphans"},
			{"compose", "-f", "docker-compose.prod.yml", "down", "-v", "--remove-orphans"},
			{"compose", "-f", "docker-compose.test.yml", "down", "-v", "--remove-orphans"},
			{"system", "prune", "-f"},
		}

		for _, args := range cmds {
			RunCommand("docker", args...)
		}

		log.Info("Cleanup completed.")
	},
}

var restartCmd = &cobra.Command{
	Use:   "restart",
	Short: "Restart current environment",
	Run: func(cmd *cobra.Command, args []string) {
		CheckDocker()
		env := GetEnv()
		log.Info("Restarting environment...", "mode", env)

		composeFiles := GetComposeFile(env)

		// Down then Up
		downArgs := append(append([]string{"compose"}, composeFiles...), "down")
		RunCommand("docker", downArgs...)

		upArgs := append(append([]string{"compose"}, composeFiles...), "up", "-d")
		if err := RunCommand("docker", upArgs...); err != nil {
			log.Fatal("Failed to start environment:", err)
		}

		log.Info("Environment restarted!")
	},
}

var buildCmd = &cobra.Command{
	Use:   "build",
	Short: "Rebuild containers",
	Run: func(cmd *cobra.Command, args []string) {
		CheckDocker()
		env := GetEnv()
		log.Info("Rebuilding containers...", "mode", env)

		composeFiles := GetComposeFile(env)
		buildArgs := append(append([]string{"compose"}, composeFiles...), "build")

		if err := RunCommand("docker", buildArgs...); err != nil {
			log.Fatal("Build failed:", err)
		}

		log.Info("Build completed successfully!")
	},
}

func init() {
	RootCmd.AddCommand(downCmd)
	RootCmd.AddCommand(cleanCmd)
	RootCmd.AddCommand(restartCmd)
	RootCmd.AddCommand(buildCmd)
}
