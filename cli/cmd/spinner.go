package cmd

import (
	"fmt"

	"github.com/charmbracelet/bubbles/spinner"
	tea "github.com/charmbracelet/bubbletea"
	"github.com/charmbracelet/lipgloss"
)

type errMsg error

type spinnerModel struct {
	spinner  spinner.Model
	text     string
	action   func() error
	err      error
	done     bool
	quitting bool
}

func initialSpinnerModel(text string, action func() error) spinnerModel {
	s := spinner.New()
	s.Spinner = spinner.Dot
	s.Style = lipgloss.NewStyle().Foreground(lipgloss.Color("205"))
	return spinnerModel{spinner: s, text: text, action: action}
}

func (m spinnerModel) Init() tea.Cmd {
	return tea.Batch(
		m.spinner.Tick,
		func() tea.Msg {
			err := m.action()
			if err != nil {
				return errMsg(err)
			}
			return true // Success msg
		},
	)
}

func (m spinnerModel) Update(msg tea.Msg) (tea.Model, tea.Cmd) {
	switch msg := msg.(type) {
	case tea.KeyMsg:
		if msg.String() == "ctrl+c" {
			m.quitting = true
			return m, tea.Quit
		}
	case errMsg:
		m.err = msg
		m.done = true
		return m, tea.Quit
	case bool:
		m.done = true
		return m, tea.Quit
	case spinner.TickMsg:
		var cmd tea.Cmd
		m.spinner, cmd = m.spinner.Update(msg)
		return m, cmd
	}
	return m, nil
}

func (m spinnerModel) View() string {
	if m.err != nil {
		return fmt.Sprintf("✖ %s: %v\n", m.text, m.err)
	}
	if m.done {
		return fmt.Sprintf("✓ %s\n", m.text)
	}
	return fmt.Sprintf("%s %s%s", m.spinner.View(), m.text, " ...")
}

// RunSpinner executes an action showing a spinner.
func RunSpinner(text string, action func() error) error {
	p := tea.NewProgram(initialSpinnerModel(text, action))
	m, err := p.Run()
	if err != nil {
		return err
	}
	if model, ok := m.(spinnerModel); ok {
		if model.err != nil {
			return model.err
		}
	}
	return nil
}
