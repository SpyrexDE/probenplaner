package cmd

import (
	"fmt"

	"github.com/charmbracelet/huh"
	"github.com/charmbracelet/log"
	"github.com/spf13/cobra"
)

var logsCmd = &cobra.Command{
	Use:   "logs [service...]",
	Short: "View logs (use -f to follow)",
	Run: func(cmd *cobra.Command, args []string) {
		CheckDocker()

		follow, _ := cmd.Flags().GetBool("follow")

		var services []string

		if len(args) > 0 {
			services = args
		} else {
			// Dynamic Selection
			available, err := GetAllServices()
			if err != nil {
				log.Warn("Failed to fetch services, using defaults.", "err", err)
				available = []string{"web", "db", "phpmyadmin"}
			}

			// Convert to Options
			var options []huh.Option[string]
			for _, s := range available {
				options = append(options, huh.NewOption(s, s))
			}

			// Interactive Selection
			form := huh.NewForm(
				huh.NewGroup(
					huh.NewMultiSelect[string]().
						Title("Select Services to Log").
						Options(options...).
						Value(&services),
				),
			)

			if err := form.Run(); err != nil {
				return
			}
		}

		if len(services) == 0 {
			log.Info("No services selected.")
			return
		}

		env := GetEnv()
		composeFiles := GetComposeFile(env)

		runArgs := append(append([]string{"compose"}, composeFiles...), "logs")
		if follow {
			runArgs = append(runArgs, "-f")
		} else {
			runArgs = append(runArgs, "--tail", "100")
		}
		runArgs = append(runArgs, services...)

		err := RunCommand("docker", runArgs...)
		if err != nil {
			fmt.Println("Logs command finished.")
		}
	},
}

var shellCmd = &cobra.Command{
	Use:   "shell [service]",
	Short: "Open shell in a container",
	Run: func(cmd *cobra.Command, args []string) {
		CheckDocker()

		var service string

		if len(args) > 0 {
			service = args[0]
		} else {
			// Dynamic Selection
			available, err := GetRunningServices()
			if err != nil {
				log.Warn("Failed to fetch services, using defaults.", "err", err)
				available = []string{"web", "db"}
			}

			// Convert to Options
			var options []huh.Option[string]
			for _, s := range available {
				options = append(options, huh.NewOption(s, s))
			}

			// Interactive Selection
			form := huh.NewForm(
				huh.NewGroup(
					huh.NewSelect[string]().
						Title("Select Container for Shell").
						Options(options...).
						Value(&service),
				),
			)

			if err := form.Run(); err != nil {
				return
			}
		}

		log.Info("Opening shell...", "service", service)

		env := GetEnv()
		composeFiles := GetComposeFile(env)

		// Try bash first
		execArgs := append(append([]string{"compose"}, composeFiles...), "exec", "-it", service, "bash")

		err := RunCommand("docker", execArgs...)
		if err != nil {
			log.Warn("bash failed, trying sh...", "err", err)
			execArgsSh := append(append([]string{"compose"}, composeFiles...), "exec", "-it", service, "sh")
			if err := RunCommand("docker", execArgsSh...); err != nil {
				log.Error("Shell exited with error:", err)
			}
		}
	},
}

func init() {
	logsCmd.Flags().BoolP("follow", "f", false, "Follow log output")
	RootCmd.AddCommand(logsCmd)
	RootCmd.AddCommand(shellCmd)
}
