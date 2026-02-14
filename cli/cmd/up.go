package cmd

import (
	"fmt"
	"os" // Added os import for os.WriteFile

	"github.com/charmbracelet/log"
	"github.com/spf13/cobra"
)

func startEnv(env string) {
	CheckDocker()

	// If env provided, save it as sticky
	if env != "" {
		err := os.WriteFile(envFileMode, []byte(env), 0644)
		if err != nil {
			log.Fatal("Failed to save environment mode:", err)
		}
		log.Info("Environment set", "mode", env)
	} else {
		env = GetEnv()
	}

	log.Info("Starting environment...", "mode", env)

	composeFiles := GetComposeFile(env)
	runArgs := append(append([]string{"compose"}, composeFiles...), "up", "-d")

	var output string
	err := RunSpinner("Starting containers (this may take a moment)...", func() error {
		var e error
		output, e = RunCommandCapture("docker", runArgs...)
		return e
	})

	if err != nil {
		log.Error("Failed to start environment")
		fmt.Println(output) // Print the captured stderr/stdout
		return
	}

	// Verify Web Container is running
	log.Info("Verifying startup...")
	_, err = GetWebContainer()
	if err != nil {
		log.Warn("⚠️  Web container is NOT running!")

		// Attempt to get logs
		log.Info("Fetching recent logs for diagnosis...")
		logArgs := append(append([]string{"compose"}, composeFiles...), "logs", "--tail", "20", "web")
		logs, _ := RunCommandCapture("docker", logArgs...)
		fmt.Println(logs)
	} else {
		log.Info("Environment started successfully!")
		log.Info("Web interface should be reachable.", "url", "http://localhost:8080")
	}
}

var upCmd = &cobra.Command{
	Use:   "up",
	Short: "Start the application environment",
	Long:  `Starts the application based on the active environment mode.`, // Updated Long description
	Run: func(cmd *cobra.Command, args []string) {
		startEnv("")
	},
}

func init() {
	RootCmd.AddCommand(upCmd)
}
