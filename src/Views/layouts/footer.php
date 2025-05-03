<?php if(isset($_SESSION['username'])): ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <!-- Load JavaScript libraries -->
    <script src="/assets/bootstrap/js/bootstrap.min.js"></script>
    <script src="/assets/js/script.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Toggle menu
        $("#menu-toggle").click(function(e) {
            e.preventDefault();
            
            if ($("#wrapper").length) {
                $("#wrapper").toggleClass("toggled");
                
                // Apply the CSS changes that match the original site's behavior
                if ($("#wrapper").hasClass("toggled")) {
                    if ($(window).width() >= 768) {
                        // Desktop behavior
                        $("#wrapper").css("padding-left", "0");
                        $("#sidebar-wrapper").css("width", "0");
                    } else {
                        // Mobile behavior
                        $("#wrapper").css("padding-left", "250px");
                        $("#sidebar-wrapper").css("width", "250px");
                    }
                } else {
                    if ($(window).width() >= 768) {
                        // Desktop behavior
                        $("#wrapper").css("padding-left", "250px");
                        $("#sidebar-wrapper").css("width", "250px");
                    } else {
                        // Mobile behavior
                        $("#wrapper").css("padding-left", "0");
                        $("#sidebar-wrapper").css("width", "0");
                    }
                }
                
                // Force browser to repaint
                if ($("#sidebar-wrapper").length) {
                    $("#sidebar-wrapper")[0].offsetHeight;
                }
                if ($("#wrapper").length) {
                    $("#wrapper")[0].offsetHeight;
                }
            }
        });
        
        // Tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Handle attendance button clicks
        $(".checkBtn, .crossBtn").on("click", function() {
            var id = $(this).attr("id");
            var attending = $(this).hasClass("checkBtn");
            
            // Don't do anything if already selected
            if (!$(this).hasClass("deselected")) {
                return;
            }
            
            // Update the UI immediately for better UX
            if (attending) {
                $(this).removeClass("deselected");
                $(this).closest(".greenOut").find(".crossBtn").addClass("deselected");
                $(this).closest(".greenOut").removeClass("redOut");
            } else {
                $(this).removeClass("deselected");
                $(this).closest(".greenOut").find(".checkBtn").addClass("deselected");
                $(this).closest(".greenOut").addClass("redOut");
            }
            
            // Send AJAX request to update attendance
            $.ajax({
                url: "/promises/update",
                method: "POST",
                data: {
                    id: id,
                    status: attending ? 1 : 0
                },
                dataType: "json",
                success: function(response) {
                    if (!response.success) {
                        Swal.fire({
                            title: 'Fehler',
                            text: response.message || 'Es ist ein Fehler aufgetreten.',
                            icon: 'error',
                            confirmButtonColor: '#478cf4'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Fehler',
                        text: 'Verbindungsfehler. Bitte versuche es später erneut.',
                        icon: 'error',
                        confirmButtonColor: '#478cf4'
                    });
                }
            });
        });
        
        // Show note icons on hover for desktop
        if (window.innerWidth >= 768) {
            $(document).on("mouseenter", ".greenOut", function() {
                $(this).find(".addNoteBtn").removeClass("hideIcon").addClass("showIcon");
            }).on("mouseleave", ".greenOut", function() {
                if (!$(this).find(".addNoteBtn").hasClass("fa-pen-square")) {
                    $(this).find(".addNoteBtn").removeClass("showIcon").addClass("hideIcon");
                }
            });
        } else {
            // For mobile, keep note icons visible
            $(".addNoteBtn").removeClass("hideIcon").addClass("showIcon");
        }
        
        // Handle note button clicks
        $(".addNoteBtn").on("click", function() {
            var id = $(this).attr("id").replace("icon", "");
            var currentIcon = $(this).hasClass("fa-plus-square") ? "plus" : "pen";
            
            Swal.fire({
                title: 'Anmerkung',
                input: 'textarea',
                inputPlaceholder: 'Deine Anmerkung eingeben...',
                showCancelButton: true,
                confirmButtonText: 'Speichern',
                cancelButtonText: 'Abbrechen',
                confirmButtonColor: '#478cf4'
            }).then((result) => {
                if (result.isConfirmed) {
                    var note = result.value;
                    
                    // Send AJAX request to update note
                    $.ajax({
                        url: "/promises/note",
                        method: "POST",
                        data: {
                            id: id,
                            note: note
                        },
                        dataType: "json",
                        success: function(response) {
                            if (response.success) {
                                // Update icon if needed
                                if (note && currentIcon === "plus") {
                                    $("#icon" + id).removeClass("fa-plus-square").addClass("fa-pen-square");
                                    $("#icon" + id).css("color", "");
                                } else if (!note && currentIcon === "pen") {
                                    $("#icon" + id).removeClass("fa-pen-square").addClass("fa-plus-square");
                                    $("#icon" + id).css("color", "lightgrey");
                                }
                            } else {
                                Swal.fire({
                                    title: 'Fehler',
                                    text: response.message || 'Es ist ein Fehler aufgetreten.',
                                    icon: 'error',
                                    confirmButtonColor: '#478cf4'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Fehler',
                                text: 'Verbindungsfehler. Bitte versuche es später erneut.',
                                icon: 'error',
                                confirmButtonColor: '#478cf4'
                            });
                        }
                    });
                }
            });
        });
    });
    
    // Helper function to show old/current entries
    function openOld() {
        var currentUrl = window.location.href;
        var newUrl;
        
        if (currentUrl.indexOf('showOld=true') > -1) {
            // Currently showing old entries, switch to only current ones
            Swal.fire({
                title: 'Zur relevanten Ansicht wechseln?',
                text: 'In der relevanten Ansicht werden nur zukünftige Proben angezeigt.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Relevante Ansicht',
                cancelButtonText: 'Abbrechen',
                confirmButtonColor: '#478cf4'
            }).then((result) => {
                if (result.isConfirmed) {
                    newUrl = currentUrl.replace(/[?&]showOld=true/, '');
                    window.location.href = newUrl;
                }
            });
        } else {
            // Currently showing only current entries, switch to all entries
            Swal.fire({
                title: 'Zur vollständigen Ansicht wechseln?',
                text: 'In der vollständigen Ansicht werden auch bereits vergangene Proben angezeigt.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Vollständige Ansicht',
                cancelButtonText: 'Abbrechen',
                confirmButtonColor: '#478cf4'
            }).then((result) => {
                if (result.isConfirmed) {
                    newUrl = currentUrl + (currentUrl.indexOf('?') > -1 ? '&' : '?') + 'showOld=true';
                    window.location.href = newUrl;
                }
            });
        }
    }
    </script>

    <?php if (isset($_SESSION['alerts']) && !empty($_SESSION['alerts'])): ?>
    <script>
        <?php foreach ($_SESSION['alerts'] as $key => $alert): ?>
            Swal.fire({
                title: '<?= htmlspecialchars($alert[0]) ?>',
                text: '<?= htmlspecialchars($alert[1]) ?>',
                icon: '<?= $alert[2] === 'error' ? 'error' : ($alert[2] === 'success' ? 'success' : 'info') ?>',
                confirmButtonColor: '#478cf4'
            });
        <?php unset($_SESSION['alerts'][$key]); endforeach; ?>
    </script>
    <?php endif; ?>
</body>
</html> 