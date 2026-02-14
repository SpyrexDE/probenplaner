package cmd

import (
	"fmt"

	"github.com/charmbracelet/bubbles/list"
	tea "github.com/charmbracelet/bubbletea"
	"github.com/charmbracelet/lipgloss"
)

const listHeight = 10

var (
	titleStyle        = lipgloss.NewStyle().MarginLeft(2)
	itemStyle         = lipgloss.NewStyle().PaddingLeft(4)
	selectedItemStyle = lipgloss.NewStyle().PaddingLeft(2).Foreground(lipgloss.Color("170"))
	paginationStyle   = list.DefaultStyles().PaginationStyle.PaddingLeft(4)
	helpStyle         = list.DefaultStyles().HelpStyle.PaddingLeft(4).PaddingBottom(1)
	quitTextStyle     = lipgloss.NewStyle().Margin(1, 0, 2, 4)
)

type item struct {
	title, desc, value string
}

func (i item) FilterValue() string { return i.title + " " + i.desc }
func (i item) Title() string       { return i.title }
func (i item) Description() string { return i.desc }

type menuModel struct {
	list     list.Model
	choice   string
	quitting bool
}

func (m menuModel) Init() tea.Cmd {
	return nil
}

func (m menuModel) Update(msg tea.Msg) (tea.Model, tea.Cmd) {
	switch msg := msg.(type) {
	case tea.KeyMsg:
		switch keypress := msg.String(); keypress {
		case "ctrl+c":
			m.quitting = true
			return m, tea.Quit

		case "enter":
			i, ok := m.list.SelectedItem().(item)
			if ok {
				m.choice = i.value
			}
			return m, tea.Quit
		}

	case tea.WindowSizeMsg:
		h, v := listStyle.GetFrameSize()
		// Cap height at 17 rows to keep dashboard visible
		maxHeight := 17
		height := msg.Height - v
		if height > maxHeight {
			height = maxHeight
		}
		m.list.SetSize(msg.Width-h, height)
	}

	var cmd tea.Cmd
	m.list, cmd = m.list.Update(msg)
	return m, cmd
}

func (m menuModel) View() string {
	if m.choice != "" {
		return ""
	}
	if m.quitting {
		return quitTextStyle.Render("Bye!")
	}
	return "\n" + m.list.View()
}

// RunMenu displays the main dashboard menu and returns the selected action

func RunMenu() (string, error) {

	items := []list.Item{
		// Page 1: Environment Control (3 items)
		item{title: "🚀 Start / Restart Environment", desc: "Spin up containers", value: "up"},
		item{title: "🛑 Stop Environment", desc: "Stop all containers", value: "down"},
		item{title: "🔁 Switch Environment", desc: "Switch between dev, prod, test", value: "switch"},

		// Page 2: Development & Database (3 items)
		item{title: "💻 Shell Access", desc: "Open shell in a container", value: "shell"},
		item{title: "📋 View Logs", desc: "Stream logs from services", value: "logs"},
		item{title: "📦 Migrations", desc: "Manage database migrations", value: "migrate"},

		// Page 3: Data & Maintenance (3 items)
		item{title: "💾 Backups", desc: "Create, list, and restore backups", value: "backup"},
		item{title: "🔨 Rebuild Containers", desc: "Force rebuild of images", value: "build"},
		item{title: "🧹 Clean System", desc: "Remove containers & volumes", value: "clean"},

		// Page 4: System & Exit (3 items)
		item{title: "🩺 System Doctor", desc: "Diagnose configuration", value: "doctor"},
		item{title: "🔄 Check for Updates", desc: "Check git tags and pull updates", value: "update"},
		item{title: "❌ Exit", desc: "Exit the CLI", value: "exit"},
	}

	const defaultWidth = 30 // wider for descriptions

	l := list.New(items, list.NewDefaultDelegate(), defaultWidth, listHeight)
	l.Title = "Dashboard Actions"
	l.SetShowStatusBar(false)
	l.SetFilteringEnabled(true)
	l.Styles.Title = titleStyle
	l.Styles.PaginationStyle = paginationStyle
	l.Styles.HelpStyle = helpStyle

	m := menuModel{list: l}

	p := tea.NewProgram(m)
	finalModel, err := p.Run()
	if err != nil {
		return "", err
	}

	if finalMenu, ok := finalModel.(menuModel); ok {
		if finalMenu.quitting {
			return "exit", nil
		}
		return finalMenu.choice, nil
	}

	return "", fmt.Errorf("could not retrieve selection")
}

var listStyle = lipgloss.NewStyle().Margin(1, 2)
