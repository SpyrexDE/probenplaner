package cmd

import (
	"os/exec"
	"runtime"

	"github.com/charmbracelet/log"
	"github.com/spf13/cobra"
)

var openCmd = &cobra.Command{
	Use:   "open",
	Short: "Open the web application in browser",
	Run: func(cmd *cobra.Command, args []string) {
		// Env check not strictly needed but good context
		env := GetEnv()

		// Port logic based on env?
		// dev/prod -> 8080 usually (mapped in docker-compose.yml)
		// but test uses 8090 in docker-compose.test.yml

		url := "http://localhost:8080"
		if env == EnvTest {
			url = "http://localhost:8090"
		}

		log.Info("Opening browser...", "url", url)

		var err error
		switch runtime.GOOS {
		case "linux":
			err = exec.Command("xdg-open", url).Start()
		case "windows":
			err = exec.Command("rundll32", "url.dll,FileProtocolHandler", url).Start()
		case "darwin":
			err = exec.Command("open", url).Start()
		default:
			log.Warn("Unsupported platform for open command.")
		}

		if err != nil {
			log.Error("Failed to open browser:", err)
		}
	},
}

func init() {
	RootCmd.AddCommand(openCmd)
}
