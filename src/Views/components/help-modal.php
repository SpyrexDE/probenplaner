<?php

/**
 * Help Modal Component
 * Context-aware help system that shows different content based on current route
 */
?>

<script>
    // Show help function with content from old site
    function showHelp() {
        const currentRoute = window.location.pathname;
        let helpTitle = 'Hilfe';
        let helpContent = '';

        // Provide different help content based on the current route
        if (currentRoute.startsWith('/promises/admin')) {
            // Director view of all promises/responses
            helpTitle = 'Hilfe - Rückmeldungen verwalten';
            helpContent = '<div style="text-align: left;">' +
                '<h4><i class="fas fa-table"></i> Übersicht</h4>' +
                '<p>Diese Seite zeigt alle Rückmeldungen zu den Proben in einer übersichtlichen Dashboard-Ansicht.</p>' +
                '<p><strong>Proben-Karten:</strong> Jede Probe wird als Karte mit Datum, Uhrzeit, Ort und Teilnahme-Statistiken angezeigt.</p>' +
                '<p><strong>Farbkodierung:</strong> Die Teilnahme-Balken sind farbkodiert - Grün für Zusagen, Rot für Absagen, Grau für fehlende Rückmeldungen.</p>' +

                '<h4><i class="fas fa-users"></i> Teilnehmer-Details</h4>' +
                '<p><strong>Aufklappbare Bereiche:</strong> Klicken Sie auf die Registerbezeichnungen (z.B. "Violine 1", "Blechbläser"), um die einzelnen Mitglieder zu sehen.</p>' +
                '<p><strong>Status-Icons:</strong> ✓ = Teilnahme, ✗ = Absage, ? = Keine Rückmeldung</p>' +
                '<p><strong>Notizen einsehen:</strong> Absage-Begründungen und Notizen werden direkt neben den Namen angezeigt (z.B. "Notiz: Krank" oder "Notiz: Terminkonflikt").</p>' +

                '<h4><i class="fas fa-brain"></i> Proben-Insights (Beta)</h4>' +
                '<p>Wenn in den Orchester-Einstellungen aktiviert, sehen Sie zusätzlich:</p>' +
                '<p><strong>Kritische Register:</strong> Register mit besonders niedriger Teilnahme werden hervorgehoben.</p>' +
                '<p><strong>Auffälligkeiten:</strong> Das System erkennt automatisch ungewöhnliche Muster:</p>' +
                '<ul style="margin-left: 20px;">' +
                '<li><strong>Teilnahme-Anomalien:</strong> Ungewöhnlich hohe/niedrige Teilnahme verglichen mit der Historie</li>' +
                '<li><strong>Rückmeldungs-Anomalien:</strong> Ungewöhnlich wenige Rückmeldungen in bestimmten Registern</li>' +
                '<li><strong>Trends:</strong> Langfristige Veränderungen der Teilnahme-Bereitschaft</li>' +
                '<li><strong>Rekorde:</strong> Neue Höchst- oder Tiefstände bei Teilnahme oder Rückmeldungen</li>' +
                '</ul>' +
                '<p><em>Diese Insights helfen dabei, Probleme frühzeitig zu erkennen und gezielt zu handeln.</em></p>' +

                '<h4><i class="fas fa-filter"></i> Navigation & Filter</h4>' +
                '<p><strong>Zeitraum-Filter:</strong> Mit dem Uhren-Symbol können Sie vergangene Proben ein-/ausblenden.</p>' +
                '<p><strong>Drucken:</strong> Nutzen Sie das Drucker-Symbol für eine druckfreundliche Ansicht.</p>' +
                '</div>';
        } else if (currentRoute.startsWith('/promises/leader')) {
            // Group leader view of responses
            helpTitle = 'Hilfe - Gruppen-Rückmeldungen';
            helpContent = '<div style="text-align: left;">' +
                '<h4><i class="fas fa-users"></i> Gruppen-Übersicht</h4>' +
                '<p>Als Stimmführung sehen Sie hier die Rückmeldungen Ihrer Instrumentengruppe in einer übersichtlichen Dashboard-Ansicht.</p>' +

                '<h4><i class="fas fa-list"></i> Proben-Karten</h4>' +
                '<p><strong>Probe-Informationen:</strong> Jede Probe wird als Karte mit Datum, Uhrzeit, Ort und Teilnahme-Statistiken Ihrer Gruppe angezeigt.</p>' +
                '<p><strong>Farbkodierung:</strong> Grün = Zusagen, Rot = Absagen, Grau = Fehlende Rückmeldungen</p>' +
                '<p><strong>Mitglieder-Details:</strong> Die Namen Ihrer Gruppenmitglieder sind mit ihrem Status aufgelistet.</p>' +
                '<p><strong>Status-Icons:</strong> ✓ = Teilnahme, ✗ = Absage, ? = Keine Rückmeldung</p>' +

                '<h4><i class="fas fa-comment-dots"></i> Notizen einsehen</h4>' +
                '<p>Absage-Begründungen und Notizen werden direkt neben den Mitgliedernamen angezeigt.</p>' +
                '<p>Dies hilft Ihnen, die Gründe für Absagen auf einen Blick zu erkennen und bei Bedarf Rücksprache zu halten.</p>' +

                '<h4><i class="fas fa-clock"></i> Navigation</h4>' +
                '<p><strong>Zeitraum-Filter:</strong> Mit dem Uhren-Symbol können Sie vergangene Proben ein-/ausblenden.</p>' +
                '<p><strong>Drucken:</strong> Das Drucker-Symbol erstellt eine druckfreundliche Übersicht.</p>' +

                '<p><em>Hinweis: Die erweiterten Proben-Insights sind für die Stimmführung nicht sichtbar - diese stehen nur der Dirigentin/dem Dirigenten zur Verfügung.</em></p>' +
                '</div>';
        } else if (currentRoute.startsWith('/promises')) {
            // Individual member view of their promises
            helpTitle = 'Hilfe - Meine Rückmeldungen';
            helpContent = '<div style="text-align: left;">' +
                '<h4><i class="fas fa-calendar-check"></i> Rückmeldungen verwalten</h4>' +
                '<p>Hier können Sie Ihre An- und Abmeldungen für kommende Proben verwalten. Die Proben werden in einer modernen Dashboard-Ansicht angezeigt.</p>' +

                '<h4><i class="fas fa-mouse-pointer"></i> Teilnahme bestätigen/absagen</h4>' +
                '<p><strong>Klicken zum Antworten:</strong> Klicken Sie auf eine beliebige Stelle einer Probe-Karte, um Ihre Teilnahme zu bestätigen oder abzusagen.</p>' +
                '<p><strong>Status-Anzeige:</strong> Ihre aktuelle Rückmeldung wird farblich hervorgehoben:</p>' +
                '<ul style="margin-left: 20px;">' +
                '<li><strong>Grün:</strong> Sie haben zugesagt</li>' +
                '<li><strong>Rot:</strong> Sie haben abgesagt</li>' +
                '<li><strong>Grau:</strong> Noch keine Rückmeldung</li>' +
                '</ul>' +

                '<h4><i class="fas fa-comment"></i> Notizen hinzufügen</h4>' +
                '<p><strong>Absage-Begründung:</strong> Bei einer Absage können Sie optional einen Grund angeben - dies hilft der Dirigentin/dem Dirigenten bei der Planung.</p>' +
                '<p><strong>Notizen bearbeiten:</strong> Sie können Ihre Notizen jederzeit nachträglich bearbeiten, indem Sie erneut auf die Probe klicken.</p>' +

                '<h4><i class="fas fa-eye"></i> Sichtbarkeit</h4>' +
                '<p><strong>Relevante Proben:</strong> Es werden nur Proben angezeigt, bei denen Ihr Instrument/Register benötigt wird.</p>' +
                '<p><strong>Zeitraum:</strong> Vergangene Proben werden automatisch ausgeblendet, um die Übersicht aktuell zu halten.</p>' +
                '<p><strong>Uhren-Symbol:</strong> Mit dem Uhren-Symbol können Sie trotzdem vergangene Proben einblenden.</p>' +
                '</div>';
        } else if (currentRoute.startsWith('/rehearsals')) {
            // Rehearsal management for directors
            helpTitle = 'Hilfe - Proben verwalten';
            helpContent = '<p>Um eine Probe zu bearbeiten, klicken Sie auf den Stift.</p>' +
                '<p>Um eine Probe zu löschen, klicken Sie auf den Mülleimer.</p>' +
                '<p>Um eine neue Probe anzulegen, klicken Sie unten rechts auf das Plus.</p>' +
                '<p>Klicken Sie auf das Uhrsymbol in der oberen rechten Ecke, um vergangene Proben ein- und auszublenden.</p>';
        } else if (currentRoute.startsWith('/probenplan')) {
            // Rehearsal plan for members
            helpTitle = 'Hilfe - Probenplan';
            helpContent = '<p>Hier sehen Sie den aktuellen Probenplan.</p>' +
                '<p>Sie können zwischen personalisierter und vollständiger Ansicht wechseln.</p>' +
                '<p>In der personalisierten Ansicht werden nur Proben angezeigt, die für Ihre Stimme relevant sind.</p>' +
                '<p>Mit dem Uhr-Symbol können Sie vergangene Proben ein- oder ausblenden.</p>' +
                '<p>Mit dem Drucker-Symbol können Sie den Probenplan ausdrucken.</p>';
        } else if (currentRoute.startsWith('/profile')) {
            // User profile
            helpTitle = 'Hilfe - Profil bearbeiten';
            helpContent = '<p>Hier können Sie Ihre persönlichen Daten und Einstellungen bearbeiten.</p>' +
                '<p>Ändern Sie Ihr Passwort, Ihren Namen oder Ihre Kontaktdaten nach Bedarf.</p>' +
                '<p>Vergessen Sie nicht, Ihre Änderungen zu speichern.</p>';
        } else {
            // Default help content
            helpContent = '<p>Willkommen im Probenplaner!</p>' +
                '<p>Verwenden Sie die Navigation, um zwischen den verschiedenen Funktionen zu wechseln.</p>' +
                '<p>Bei Fragen zur Bedienung klicken Sie auf das Fragezeichen-Symbol.</p>';
        }

        Swal.fire({
            title: helpTitle,
            html: helpContent,
            icon: 'info',
            confirmButtonColor: '#478cf4'
        });
    }
</script>