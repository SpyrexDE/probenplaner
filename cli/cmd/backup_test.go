package cmd

import (
	"fmt"
	"os"
	"path/filepath"
	"sort"
	"testing"
	"time"
)

func TestExpiredBackups(t *testing.T) {
	now := time.Date(2026, 11, 12, 3, 0, 0, 0, time.Local)

	// One backup per night for 500 nights, ending today
	var backups []backupFile
	for i := 0; i < 500; i++ {
		d := now.AddDate(0, 0, -i)
		backups = append(backups, backupFile{name: d.Format("backup_2006-01-02_15-04") + "_v1.sql.gz", time: d})
	}

	expired := map[string]bool{}
	for _, b := range expiredBackups(backups, now) {
		expired[b.name] = true
	}
	var kept []string
	for _, b := range backups {
		if !expired[b.name] {
			kept = append(kept, b.name[len("backup_"):len("backup_2006-01-02")])
		}
	}
	sort.Strings(kept)

	// 12.11.2025 is the oldest night inside the year, so it stands for November 2025
	want := []string{"2025-11-12"}
	for m := time.Date(2025, 12, 1, 0, 0, 0, 0, time.Local); m.Before(time.Date(2026, 11, 1, 0, 0, 0, 0, time.Local)); m = m.AddDate(0, 1, 0) {
		want = append(want, m.Format("2006-01-02"))
	}
	// 01.10. is already in the list as the monthly backup
	for d := time.Date(2026, 10, 13, 0, 0, 0, 0, time.Local); !d.After(now); d = d.AddDate(0, 0, 1) {
		want = append(want, d.Format("2006-01-02"))
	}

	if fmt.Sprint(kept) != fmt.Sprint(want) {
		t.Errorf("kept\n%v\nwant\n%v", kept, want)
	}
}

func TestBackupTimeIgnoresModTime(t *testing.T) {
	modTime := time.Date(2026, 11, 12, 12, 0, 0, 0, time.Local)

	got := backupTime("backup_2026-04-05_12-26_v1.0.3-4-g3db1777.sql.gz", modTime)
	if want := time.Date(2026, 4, 5, 12, 26, 0, 0, time.Local); !got.Equal(want) {
		t.Errorf("got %v, want %v", got, want)
	}
	if got := backupTime("prod-dump.sql.gz", modTime); !got.Equal(modTime) {
		t.Errorf("got %v, want the mtime", got)
	}
}

func TestCleanupOnlyTouchesExpiredDumps(t *testing.T) {
	t.Chdir(t.TempDir())
	os.MkdirAll("backups/archive", 0755)

	now := time.Now()
	recent := now.AddDate(0, 0, -5).Format("backup_2006-01-02_15-04") + "_v1.sql.gz"
	oldFirst := time.Date(now.Year(), now.Month(), 1, 3, 0, 0, 0, time.Local).AddDate(0, -3, 0)
	monthly := oldFirst.Format("backup_2006-01-02_15-04") + "_v1.sql.gz"
	excess := oldFirst.AddDate(0, 0, 1).Format("backup_2006-01-02_15-04") + "_v1.sql.gz"
	archived := filepath.Join("archive", excess)

	for _, f := range []string{recent, monthly, excess, archived, "notes.txt"} {
		os.WriteFile(filepath.Join("backups", f), []byte("x"), 0644)
	}

	cleanupOldBackups()

	for f, wantKept := range map[string]bool{recent: true, monthly: true, excess: false, archived: true, "notes.txt": true} {
		_, err := os.Stat(filepath.Join("backups", f))
		if kept := err == nil; kept != wantKept {
			t.Errorf("%s: kept=%v, want %v", f, kept, wantKept)
		}
	}
}
