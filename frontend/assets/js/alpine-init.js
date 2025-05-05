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
              "Please login to access the admin dashboard."
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
        // Try both authMiddleware (if that's your implementation) or authApiService
        if (
          window.authMiddleware &&
          typeof window.authMiddleware.logout === "function"
        ) {
          await window.authMiddleware.logout();
        } else if (
          window.authApiService &&
          typeof window.authApiService.logout === "function"
        ) {
          await window.authApiService.logout();
        } else {
          console.warn(
            "No logout service found. Performing client-side logout only."
          );
        }

        // Clear auth data from localStorage
        localStorage.removeItem("auth_token");
        localStorage.removeItem("user");
        localStorage.removeItem("roles");
        localStorage.removeItem("permissions");

        // Update state
        this.isAuthenticated = false;
        this.user = null;
        this.roles = [];
        this.permissions = [];

        // Redirect to login
        window.location.hash = "login";

        // Show success toast
        if (window.appToast) {
          window.appToast.success("You have successfully logged out.");
        }
      } catch (error) {
        console.error("Logout error:", error);

        // Show error toast
        if (window.appToast) {
          window.appToast.error("Failed to logout. Please try again.");
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
                                  Please login to access the admin dashboard.
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
                              <h3>Page Not Found</h3>
                              <p class="text-muted">The page "${page}" could not be found.</p>
                              <button class="btn btn-primary" @click="setActivePage('dashboard')">Return to Dashboard</button>
                          </div>
                      </div>`;
        }
      } catch (error) {
        console.error("Error loading page content:", error);
        this.pageContent = `<div class="alert alert-danger">
                      <i class="bi bi-exclamation-triangle me-2"></i>
                      Error loading content. Please try again.
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
            // PERBAIKAN: Periksa ketersediaan layanan auth
            if (window.authApiService) {
              // Normal flow - gunakan authApiService
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
                window.appToast.success("Login successful!");
              }
            } else {
              // FALLBACK MODE: Simulasikan login untuk development/testing
              console.warn(
                "Auth service not available, using development login mode"
              );

              // Simulasikan respons login untuk pengembangan
              // CATATAN: Ini hanya untuk development, JANGAN gunakan di production!
              const mockUser = {
                id: 1,
                name: "Development User",
                email: email || "dev@example.com",
              };

              const mockToken = "dev-token-" + Date.now();

              // Simpan data autentikasi palsu di localStorage
              localStorage.setItem("auth_token", mockToken);
              localStorage.setItem("user", JSON.stringify(mockUser));
              localStorage.setItem("roles", JSON.stringify(["admin"]));
              localStorage.setItem(
                "permissions",
                JSON.stringify([
                  "view-berita",
                  "create-berita",
                  "edit-berita",
                  "delete-berita",
                  "view-pengumuman",
                  "view-agenda",
                  "view-prodi",
                  "view-kerjasama",
                  "view-mahasiswa",
                  "view-karya",
                  "manage-media",
                ])
              );

              // Update state autentikasi
              this.checkAuthState();

              // Redirect ke dashboard
              window.location.hash = "dashboard";

              // Tampilkan pesan sukses tetapi dengan warning
              if (window.appToast) {
                window.appToast.warning(
                  "Development login mode activated. API service not available."
                );
              } else {
                alert(
                  "Development login mode activated. API service not available."
                );
              }
            }
          } catch (error) {
            console.error("Login error:", error);

            // Show error message
            if (loginAlert) {
              loginAlert.classList.remove("d-none");
              const loginAlertMessage =
                document.getElementById("loginAlertMessage");
              if (loginAlertMessage) {
                loginAlertMessage.textContent =
                  error.message ||
                  "Login failed. Please check your email and password.";
              }
            }

            // Show error toast
            if (window.appToast) {
              window.appToast.error(
                error.message ||
                  "Login failed. Please check your email and password."
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

      // ... kode handler lainnya
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
  }));
});
