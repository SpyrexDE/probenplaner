package cmd

import (
	"fmt"
	"os"
	"os/exec"
	"strings"

	"github.com/charmbracelet/log"
)

// RunCommand executes a command and streams output to stdout/stderr
func RunCommand(name string, args ...string) error {
	cmd := exec.Command(name, args...)
	cmd.Stdout = os.Stdout
	cmd.Stderr = os.Stderr
	cmd.Stdin = os.Stdin
	return cmd.Run()
}

// RunCommandCapture executes a command and returns output as string
func RunCommandCapture(name string, args ...string) (string, error) {
	cmd := exec.Command(name, args...)
	out, err := cmd.CombinedOutput()
	return string(out), err
}

// RunCommandCaptureSilent executes a command without any output
func RunCommandCaptureSilent(name string, args ...string) error {
	cmd := exec.Command(name, args...)
	return cmd.Run()
}

// CheckDocker checks if Docker is running
func CheckDocker() {
	_, err := exec.Command("docker", "info").Output()
	if err != nil {
		log.Error("Docker is not running. Please start Docker Desktop and try again.")
		os.Exit(1)
	}
}

// GetWebContainer finds the running web container name

// GetWebContainer finds the running web container name
func GetWebContainer() (string, error) {
	// Logic from shell script: docker compose ps --services --filter "status=running" | grep web ...
	out, err := RunCommandCapture("docker", "compose", "ps", "--services", "--filter", "status=running")
	if err != nil {
		return "", err
	}

	lines := strings.Split(strings.TrimSpace(out), "\n")
	for _, line := range lines {
		if strings.Contains(line, "web") {
			// Now get the actual container name
			name, err := RunCommandCapture("docker", "compose", "ps", line, "--format", "{{.Name}}")
			if err != nil {
				return "", err
			}
			return strings.TrimSpace(name), nil
		}
	}

	return "", fmt.Errorf("web container not found")
}

// GetRunningServices returns a list of running services from docker compose
func GetRunningServices() ([]string, error) {
	// docker compose ps --services --filter "status=running"
	// We need to pass the correct compose files based on env
	env := GetEnv()
	composeFiles := GetComposeFile(env)
	args := append(append([]string{"compose"}, composeFiles...), "ps", "--services", "--filter", "status=running")

	out, err := RunCommandCaptureSilentOutput("docker", args...)
	if err != nil {
		return nil, err
	}

	lines := strings.Split(strings.TrimSpace(out), "\n")
	var services []string
	for _, line := range lines {
		if strings.TrimSpace(line) != "" {
			services = append(services, strings.TrimSpace(line))
		}
	}
	return services, nil
}

// GetAllServices returns a list of ALL defined services from docker compose config
func GetAllServices() ([]string, error) {
	env := GetEnv()
	composeFiles := GetComposeFile(env)
	args := append(append([]string{"compose"}, composeFiles...), "config", "--services")

	out, err := RunCommandCaptureSilentOutput("docker", args...)
	if err != nil {
		return nil, err
	}

	lines := strings.Split(strings.TrimSpace(out), "\n")
	var services []string
	for _, line := range lines {
		if strings.TrimSpace(line) != "" {
			services = append(services, strings.TrimSpace(line))
		}
	}
	return services, nil
}

// RunCommandCaptureSilentOutput executes a command and returns output, suppressing stderr but returning it in error if failed
func RunCommandCaptureSilentOutput(name string, args ...string) (string, error) {
	cmd := exec.Command(name, args...)
	out, err := cmd.Output()
	return string(out), err
}
