document.addEventListener("alpine:init", () => {
  Alpine.data("appData", () => ({
    sidebarCollapsed: false,
    sidebarMobileOpen: false,
    activePage: "dashboard",
    pageContent: "",
    darkMode: false,
    isAuthenticated: false,
    user: null,
    roles: [],
    permissions: [],
    sidebarItems: [],

    init() {
      // Check authentication status first before doing anything else
      this.checkAuthState();

      // Handle responsive sidebar for mobile devices
      this.handleResponsiveSidebar();
      window.addEventListener("resize", () => this.handleResponsiveSidebar());

      // Check for dark mode preference
      this.checkDarkMode();

      // Check if there's a hash in the URL or load appropriate page
      // If not authenticated, default to login; otherwise use dashboard
      const hash =
        window.location.hash.substring(1) ||
        (this.isAuthenticated ? "dashboard" : "login");

      // Force to login page if not authenticated and trying to access protected route
      const publicRoutes = ["login", "forgot-password", "reset-password"];
      if (!this.isAuthenticated && !publicRoutes.includes(hash)) {
        window.location.hash = "login";
        this.setActivePage("login");
      } else {
        this.setActivePage(hash);
      }

      // Handle navigation with hash changes
      window.addEventListener("hashchange", () => {
        const currentHash = window.location.hash.substring(1);

        // Security check on every hash change
        if (!this.isAuthenticated && !publicRoutes.includes(currentHash)) {
          console.log("Authentication check failed, redirecting to login");
          window.location.hash = "login";
          this.setActivePage("login");

          // Show message about requiring login
          if (window.appToast) {
            window.appToast.error(
              "Silahkan login untuk mengakses dashboard admin."
            );
          }
          return;
        }

        this.setActivePage(currentHash);
      });

      // Close sidebar when clicking outside on mobile
      document.addEventListener("click", (event) => {
        if (window.innerWidth < 768 && this.sidebarMobileOpen) {
          const sidebar = document.querySelector(".sidebar");
          const sidebarToggle = document.querySelector(".sidebar-toggle");
          const mobileToggle = document.querySelector(".mobile-toggle");

          if (
            !sidebar.contains(event.target) &&
            event.target !== sidebarToggle &&
            event.target !== mobileToggle
          ) {
            this.sidebarMobileOpen = false;
          }
        }
      });
    },

    checkAuthState() {
      // Check if user is authenticated
      const token = localStorage.getItem("auth_token");
      const user = JSON.parse(localStorage.getItem("user") || "null");
      const roles = JSON.parse(localStorage.getItem("roles") || "[]");
      const permissions = JSON.parse(
        localStorage.getItem("permissions") || "[]"
      );

      // Update authentication state
      this.isAuthenticated = !!token && !!user;
      this.user = user;
      this.roles = roles;
      this.permissions = permissions;

      console.log("Authentication state:", this.isAuthenticated);
      console.log("User:", this.user);
      console.log("Roles:", this.roles);
      console.log("Permissions:", this.permissions);

      // Get sidebar items based on permissions
      if (this.isAuthenticated && window.authMiddleware) {
        this.sidebarItems = window.authMiddleware.getSidebarItems();
      } else {
        this.sidebarItems = [];
      }
    },

    hasRole(role) {
      return this.roles.includes(role);
    },

    hasPermission(permission) {
      return this.permissions.includes(permission);
    },

    async logout() {
      try {
        if (window.authMiddleware) {
          await window.authMiddleware.logout();

          // Update state
          this.isAuthenticated = false;
          this.user = null;
          this.roles = [];
          this.permissions = [];

          // Redirect to login
          window.location.hash = "login";

          // Show success toast
          if (window.appToast) {
            window.appToast.success("Anda berhasil logout.");
          }
        }
      } catch (error) {
        console.error("Logout error:", error);

        // Show error toast
        if (window.appToast) {
          window.appToast.error("Gagal logout. Silakan coba lagi.");
        }
      }
    },

    toggleSidebar() {
      this.sidebarCollapsed = !this.sidebarCollapsed;
      // Store preference in localStorage
      localStorage.setItem("sidebarCollapsed", this.sidebarCollapsed);
    },

    toggleMobileSidebar() {
      this.sidebarMobileOpen = !this.sidebarMobileOpen;
    },

    setActivePage(page) {
      // Security check - if not authenticated and trying to access protected page
      const publicPages = ["login", "forgot-password", "reset-password"];
      if (!this.isAuthenticated && !publicPages.includes(page)) {
        console.log("Not authenticated, redirecting from", page, "to login");
        window.location.hash = "login";
        this.activePage = "login";
        this.loadPageContent("login");
        return;
      }

      this.activePage = page;
      this.loadPageContent(page);

      // Close mobile sidebar when navigating
      if (window.innerWidth < 768) {
        this.sidebarMobileOpen = false;
      }
    },

    async loadPageContent(page) {
      try {
        // Final security check before loading content
        const publicPages = ["login", "forgot-password", "reset-password"];
        if (!this.isAuthenticated && !publicPages.includes(page)) {
          console.log("Security check failed in loadPageContent");
          window.location.hash = "login";

          this.pageContent = `
                        <div class="login-container">
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                Silahkan login untuk mengakses dashboard admin.
                            </div>
                            <!-- Login content will be loaded here -->
                        </div>
                    `;

          // Load login page content
          const loginResponse = await fetch("pages/login.html");
          if (loginResponse.ok) {
            this.pageContent = await loginResponse.text();
          }

          return;
        }

        // Show loading state
        this.pageContent = `
                    <div class="d-flex justify-content-center align-items-center" style="height: 70vh;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;

        const response = await fetch(`pages/${page}.html`);
        if (response.ok) {
          this.pageContent = await response.text();
          // Initialize charts and other components after content is loaded
          this.$nextTick(() => {
            this.initPageComponents();
          });
        } else {
          this.pageContent = `<div class="d-flex justify-content-center align-items-center" style="height: 70vh;">
                        <div class="text-center">
                            <div class="mb-4">
                                <i class="bi bi-exclamation-circle text-warning" style="font-size: 4rem;"></i>
                            </div>
                            <h3>Halaman Tidak Ditemukan</h3>
                            <p class="text-muted">Halaman "${page}" tidak dapat ditemukan.</p>
                            <button class="btn btn-primary" @click="setActivePage('dashboard')">Kembali ke Dashboard</button>
                        </div>
                    </div>`;
        }
      } catch (error) {
        console.error("Error loading page content:", error);
        this.pageContent = `<div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Error saat memuat konten. Silakan coba lagi.
                </div>`;
      }
    },

    initPageComponents() {
      // Initialize charts, datepickers, and other components
      if (this.activePage === "dashboard") {
        this.initDashboardCharts();
      }

      // Initialize tooltips and popovers
      const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
      if (tooltips.length > 0) {
        [...tooltips].map((tooltip) => new bootstrap.Tooltip(tooltip));
      }

      const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
      if (popovers.length > 0) {
        [...popovers].map((popover) => new bootstrap.Popover(popover));
      }

      // Any other page-specific initialization
      if (this.activePage === "analytics") {
        this.initAnalyticsCharts();
      }

      if (this.activePage === "calendar") {
        this.initCalendarEvents();
      }

      // Initialize form elements and validation on specific pages
      if (
        [
          "berita",
          "pengumuman",
          "agenda",
          "prodi",
          "kerjasama",
          "mahasiswa",
          "karya",
        ].includes(this.activePage)
      ) {
        this.initFormValidation();
      }

      // Initialize login page handlers
      if (this.activePage === "login") {
        this.initLoginHandlers();
      }
    },

    initLoginHandlers() {
      // Handle login form submission
      const loginForm = document.getElementById("loginForm");
      if (loginForm) {
        loginForm.addEventListener("submit", async (event) => {
          event.preventDefault();

          // Get form data
          const email = document.getElementById("email").value;
          const password = document.getElementById("password").value;
          const remember =
            document.getElementById("rememberMe")?.checked || false;

          // Show loading state
          const loginButton = document.getElementById("loginButton");
          const loginSpinner = document.getElementById("loginSpinner");
          const loginAlert = document.getElementById("loginAlert");

          if (loginButton && loginSpinner) {
            loginButton.disabled = true;
            loginSpinner.classList.remove("d-none");
          }

          if (loginAlert) {
            loginAlert.classList.add("d-none");
          }

          try {
            // Call authentication service
            if (window.authApiService) {
              const response = await window.authApiService.login(
                email,
                password,
                remember
              );

              // Update authentication state
              this.checkAuthState();

              // Redirect to dashboard
              window.location.hash = "dashboard";

              // Show success message
              if (window.appToast) {
                window.appToast.success("Login berhasil!");
              }
            } else {
              throw new Error("Layanan autentikasi tidak tersedia");
            }
          } catch (error) {
            console.error("Login error:", error);

            // Show error message
            if (loginAlert) {
              loginAlert.textContent =
                error.message ||
                "Login gagal. Periksa email dan password Anda.";
              loginAlert.classList.remove("d-none");
            }

            // Show error toast
            if (window.appToast) {
              window.appToast.error(
                error.message || "Login gagal. Periksa email dan password Anda."
              );
            }
          } finally {
            // Reset loading state
            if (loginButton && loginSpinner) {
              loginButton.disabled = false;
              loginSpinner.classList.add("d-none");
            }
          }
        });
      }

      // Handle "Forgot Password" link
      const forgotPasswordLink = document.getElementById("forgotPasswordLink");
      if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener("click", (e) => {
          e.preventDefault();
          window.location.hash = "forgot-password";
        });
      }

      // Handle "Register" link if exists
      const registerLink = document.getElementById("registerLink");
      if (registerLink) {
        registerLink.addEventListener("click", (e) => {
          e.preventDefault();
          window.location.hash = "register";
        });
      }
    },

    initFormValidation() {
      // Get all forms with validation
      const forms = document.querySelectorAll(".needs-validation");

      // Loop over forms and prevent submission if validation fails
      Array.from(forms).forEach((form) => {
        form.addEventListener(
          "submit",
          (event) => {
            if (!form.checkValidity()) {
              event.preventDefault();
              event.stopPropagation();
            }

            form.classList.add("was-validated");
          },
          false
        );
      });
    },

    handleResponsiveSidebar() {
      if (window.innerWidth < 768) {
        this.sidebarCollapsed = true;
        // Don't auto-open the sidebar on mobile
      } else {
        // Restore user preference from localStorage on larger screens
        const storedPreference = localStorage.getItem("sidebarCollapsed");
        if (storedPreference !== null) {
          this.sidebarCollapsed = storedPreference === "true";
        }
        // Always close mobile overlay on desktop
        this.sidebarMobileOpen = false;
      }
    },

    checkDarkMode() {
      // Check for system preference
      const prefersDarkMode = window.matchMedia(
        "(prefers-color-scheme: dark)"
      ).matches;

      // Check for stored preference
      const storedPreference = localStorage.getItem("darkMode");

      if (storedPreference !== null) {
        this.darkMode = storedPreference === "true";
      } else {
        this.darkMode = prefersDarkMode;
      }

      // Apply dark mode
      this.applyTheme();

      // Listen for system changes
      window
        .matchMedia("(prefers-color-scheme: dark)")
        .addEventListener("change", (e) => {
          if (localStorage.getItem("darkMode") === null) {
            this.darkMode = e.matches;
            this.applyTheme();
          }
        });
    },

    toggleDarkMode() {
      this.darkMode = !this.darkMode;
      localStorage.setItem("darkMode", this.darkMode);
      this.applyTheme();
    },

    applyTheme() {
      if (this.darkMode) {
        document.documentElement.setAttribute("data-bs-theme", "dark");
      } else {
        document.documentElement.setAttribute("data-bs-theme", "light");
      }
    },

    initDashboardCharts() {
      // Weekly activity chart
      const weeklyChartEl = document.getElementById("weeklyActivityChart");
      if (weeklyChartEl) {
        const labels = ["S", "M", "T", "W", "T", "F", "S"];
        const data = {
          labels: labels,
          datasets: [
            {
              label: "Activity",
              data: [15, 60, 40, 80, 20, 35, 15],
              backgroundColor: (context) => {
                const chart = context.chart;
                const { ctx, chartArea } = chart;
                if (!chartArea) return null;

                // Create gradient for each bar
                return labels.map((_, index) => {
                  if (index === 3) {
                    // Wednesday has highest value
                    return "#1e4430"; // Dark green for highest value
                  } else if (index === 1) {
                    // Monday is second highest
                    return "#3b8a5e"; // Medium green for second highest
                  } else {
                    return "#55b687"; // Regular green
                  }
                });
              },
              borderRadius: 6,
              borderWidth: 0,
            },
          ],
        };

        const config = {
          type: "bar",
          data: data,
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
              },
              tooltip: {
                enabled: true,
                backgroundColor: this.darkMode ? "#2d3748" : "#fff",
                titleColor: this.darkMode ? "#fff" : "#212529",
                bodyColor: this.darkMode ? "#fff" : "#212529",
                borderColor: this.darkMode ? "#4a5568" : "#e9ecef",
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                  title: () => "",
                  label: (context) => `Tasks: ${context.parsed.y}`,
                },
              },
            },
            scales: {
              x: {
                grid: {
                  display: false,
                },
              },
              y: {
                beginAtZero: true,
                grid: {
                  color: this.darkMode ? "rgba(255, 255, 255, 0.1)" : "#e9ecef",
                },
                ticks: {
                  callback: function (value) {
                    return value + "%";
                  },
                },
              },
            },
          },
        };

        new Chart(weeklyChartEl, config);
      }

      // Progress Chart (Doughnut)
      const progressChartEl = document.getElementById("projectProgressChart");
      if (progressChartEl) {
        const data = {
          labels: ["Completed", "In Progress", "Pending"],
          datasets: [
            {
              data: [65, 25, 10],
              backgroundColor: [
                "#3b8a5e",
                "#f9d56c",
                this.darkMode ? "#4a5568" : "#e9ecef",
              ],
              borderWidth: 0,
              cutout: "80%",
            },
          ],
        };

        const config = {
          type: "doughnut",
          data: data,
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
              },
              tooltip: {
                enabled: true,
                backgroundColor: this.darkMode ? "#2d3748" : "#fff",
                titleColor: this.darkMode ? "#fff" : "#212529",
                bodyColor: this.darkMode ? "#fff" : "#212529",
                borderColor: this.darkMode ? "#4a5568" : "#e9ecef",
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                  label: (context) => `${context.label}: ${context.parsed}%`,
                },
              },
            },
          },
        };

        new Chart(progressChartEl, config);
      }
    },

    initAnalyticsCharts() {
      // Task Performance Chart
      const taskPerformanceCtx = document.getElementById(
        "taskPerformanceChart"
      );
      if (taskPerformanceCtx) {
        const taskPerformanceChart = new Chart(taskPerformanceCtx, {
          type: "line",
          data: {
            labels: ["1", "5", "10", "15", "20", "25", "30"],
            datasets: [
              {
                label: "Completed",
                data: [5, 10, 15, 12, 18, 20, 25],
                borderColor: "#3b8a5e",
                backgroundColor: "rgba(59, 138, 94, 0.1)",
                tension: 0.4,
                fill: true,
              },
              {
                label: "Created",
                data: [7, 15, 20, 18, 25, 28, 30],
                borderColor: "#6edff6",
                backgroundColor: "rgba(110, 223, 246, 0.1)",
                tension: 0.4,
                fill: true,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "top",
                labels: {
                  usePointStyle: true,
                  pointStyle: "circle",
                },
              },
              tooltip: {
                backgroundColor: this.darkMode ? "#2d3748" : "#fff",
                titleColor: this.darkMode ? "#fff" : "#212529",
                bodyColor: this.darkMode ? "#fff" : "#212529",
                borderColor: this.darkMode ? "#4a5568" : "#e9ecef",
                borderWidth: 1,
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  precision: 0,
                },
                grid: {
                  color: this.darkMode ? "rgba(255, 255, 255, 0.1)" : "#e9ecef",
                },
              },
              x: {
                grid: {
                  color: this.darkMode ? "rgba(255, 255, 255, 0.1)" : "#e9ecef",
                },
              },
            },
          },
        });
      }

      // Task Status Chart
      const taskStatusCtx = document.getElementById("taskStatusChart");
      if (taskStatusCtx) {
        const taskStatusChart = new Chart(taskStatusCtx, {
          type: "doughnut",
          data: {
            labels: ["Completed", "In Progress", "Overdue", "Pending"],
            datasets: [
              {
                data: [45, 35, 10, 10],
                backgroundColor: [
                  "#55b687",
                  "#f9d56c",
                  "#e76f51",
                  this.darkMode ? "#4a5568" : "#adb5bd",
                ],
                borderWidth: 0,
                cutout: "70%",
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
              },
              tooltip: {
                backgroundColor: this.darkMode ? "#2d3748" : "#fff",
                titleColor: this.darkMode ? "#fff" : "#212529",
                bodyColor: this.darkMode ? "#fff" : "#212529",
                borderColor: this.darkMode ? "#4a5568" : "#e9ecef",
                borderWidth: 1,
              },
            },
          },
        });
      }
    },

    initCalendarEvents() {
      // Event handler for mini calendar dates
      const dates = document.querySelectorAll(".mini-calendar .date");
      if (dates.length > 0) {
        dates.forEach((date) => {
          date.addEventListener("click", () => {
            // Remove current-date class from all dates
            dates.forEach((d) => d.classList.remove("current-date"));
            // Add current-date class to clicked date
            date.classList.add("current-date");
          });
        });
      }

      // Make event items draggable in the future
      const eventItems = document.querySelectorAll(".event-item");
      if (eventItems.length > 0) {
        // Add hover effect
        eventItems.forEach((item) => {
          item.addEventListener("mouseover", () => {
            item.style.zIndex = "10";
          });

          item.addEventListener("mouseout", () => {
            item.style.zIndex = "1";
          });
        });
      }
    },
  }));
});
