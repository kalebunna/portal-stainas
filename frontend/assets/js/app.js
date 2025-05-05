/**
 * AdminDash - Custom Admin Dashboard
 * Main JavaScript file
 */

// Initialize tooltips
const initTooltips = () => {
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
};

// Initialize popovers
const initPopovers = () => {
  const popoverTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="popover"]')
  );
  popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });
};

// Initialize dropdowns
const initDropdowns = () => {
  const dropdownElementList = [].slice.call(
    document.querySelectorAll(".dropdown-toggle")
  );
  dropdownElementList.map(function (dropdownToggleEl) {
    return new bootstrap.Dropdown(dropdownToggleEl);
  });
};

// Handle mobile navigation
const handleMobileNav = () => {
  const handleWindowResize = () => {
    if (window.innerWidth < 992) {
      document.body.classList.add("sidebar-mobile");
    } else {
      document.body.classList.remove("sidebar-mobile");
    }
  };

  // Run on initial load
  handleWindowResize();

  // Run on resize
  window.addEventListener("resize", handleWindowResize);
};

// Authentication handlers
const initAuthHandlers = () => {
  // Login form handler
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", async (event) => {
      event.preventDefault();

      // Get form data
      const email = document.getElementById("email").value;
      const password = document.getElementById("password").value;
      const remember = document.getElementById("rememberMe")?.checked || false;

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
          window.location.hash = "dashboard";

          // Show success message
          if (window.appToast) {
            window.appToast.success("Login successful! Welcome back.");
          }

          // Reload page to update authentication state
          window.location.reload();
        } else {
          throw new Error("Authentication service not available");
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
              error.message || "Invalid credentials. Please try again.";
          }
        }

        // Add shake animation to form
        loginForm.classList.add("shake");
        setTimeout(() => {
          loginForm.classList.remove("shake");
        }, 500);
      } finally {
        // Reset button state
        if (loginButton && loginSpinner) {
          loginButton.disabled = false;
          loginSpinner.classList.add("d-none");
        }
      }
    });
  }

  // Logout handler
  const logoutButton = document.querySelector('a[href="#logout"]');
  if (logoutButton) {
    logoutButton.addEventListener("click", async (event) => {
      event.preventDefault();

      try {
        if (window.authApiService) {
          await window.authApiService.logout();

          // Clear auth data
          localStorage.removeItem("auth_token");
          localStorage.removeItem("user");
          localStorage.removeItem("roles");
          localStorage.removeItem("permissions");

          // Redirect to login
          window.location.hash = "login";

          // Reload page to update authentication state
          window.location.reload();
        }
      } catch (error) {
        console.error("Logout error:", error);

        // Show error message
        if (window.appToast) {
          window.appToast.error("Error logging out. Please try again.");
        }
      }
    });
  }

  // Password visibility toggle
  const togglePasswordButtons = document.querySelectorAll(".toggle-password");
  togglePasswordButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const passwordInput = button
        .closest(".input-group")
        .querySelector("input");
      const type =
        passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);

      const icon = button.querySelector("i");
      icon.classList.toggle("bi-eye");
      icon.classList.toggle("bi-eye-slash");
    });
  });
};

// Theme switcher functionality
const initThemeSwitcher = () => {
  const themeSwitchers = document.querySelectorAll(
    '[data-bs-toggle="tooltip"][title="Toggle Dark Mode"]'
  );

  if (themeSwitchers.length > 0) {
    // Check for saved theme preference
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme) {
      document.documentElement.setAttribute("data-bs-theme", savedTheme);
    }

    // Check for system preference if no saved preference
    if (!savedTheme) {
      const prefersDarkMode = window.matchMedia(
        "(prefers-color-scheme: dark)"
      ).matches;
      if (prefersDarkMode) {
        document.documentElement.setAttribute("data-bs-theme", "dark");
      }
    }

    // Listen for theme changes
    themeSwitchers.forEach((switcher) => {
      switcher.addEventListener("click", function () {
        const currentTheme =
          document.documentElement.getAttribute("data-bs-theme");
        const newTheme = currentTheme === "dark" ? "light" : "dark";

        document.documentElement.setAttribute("data-bs-theme", newTheme);
        localStorage.setItem("theme", newTheme);
      });
    });
  }
};

// Initialize role-based UI elements
const initRoleBasedUI = () => {
  // Check if user is authenticated
  const token = localStorage.getItem("auth_token");
  const user = JSON.parse(localStorage.getItem("user") || "null");
  const roles = JSON.parse(localStorage.getItem("roles") || "[]");
  const permissions = JSON.parse(localStorage.getItem("permissions") || "[]");

  const isAuthenticated = !!token && !!user;

  // Hide admin-only elements when not authenticated
  const adminElements = document.querySelectorAll("[data-require-auth]");
  adminElements.forEach((element) => {
    if (!isAuthenticated) {
      element.style.display = "none";
    }
  });

  // Hide elements based on permissions
  const permissionElements = document.querySelectorAll(
    "[data-require-permission]"
  );
  permissionElements.forEach((element) => {
    const requiredPermission = element.getAttribute("data-require-permission");
    if (!permissions.includes(requiredPermission)) {
      element.style.display = "none";
    }
  });

  // Hide elements based on roles
  const roleElements = document.querySelectorAll("[data-require-role]");
  roleElements.forEach((element) => {
    const requiredRole = element.getAttribute("data-require-role");
    if (!roles.includes(requiredRole)) {
      element.style.display = "none";
    }
  });

  // Update user info in UI
  const userNameElements = document.querySelectorAll(".user-name");
  userNameElements.forEach((element) => {
    if (user && user.name) {
      element.textContent = user.name;
    }
  });

  const userEmailElements = document.querySelectorAll(".user-email");
  userEmailElements.forEach((element) => {
    if (user && user.email) {
      element.textContent = user.email;
    }
  });

  const userRoleElements = document.querySelectorAll(".user-role");
  userRoleElements.forEach((element) => {
    if (roles && roles.length > 0) {
      element.textContent =
        roles[0].charAt(0).toUpperCase() + roles[0].slice(1);
    }
  });
};

// Save sidebar state in localStorage
const saveSidebarState = (collapsed) => {
  localStorage.setItem("sidebarCollapsed", collapsed);
};

// Load sidebar state from localStorage
const loadSidebarState = () => {
  const state = localStorage.getItem("sidebarCollapsed");
  return state === "true";
};

// Initialize dashboard charts
const initDashboardCharts = () => {
  // This will be handled by Alpine.js
};

// Initialize the application
const initApp = () => {
  // Initialize Bootstrap components
  initTooltips();
  initPopovers();
  initDropdowns();

  // Initialize app features
  handleMobileNav();
  initThemeSwitcher();
  initAuthHandlers();
  initRoleBasedUI();

  // Initialize dynamic content
  const initDynamicContent = () => {
    // Add event listeners to dynamically loaded content
    initTooltips();
    initPopovers();

    // Initialize form validation
    const forms = document.querySelectorAll(".needs-validation");
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
  };

  // Add mutation observer to watch for dynamic content
  const contentArea = document.querySelector(".content-area");
  if (contentArea) {
    const observer = new MutationObserver(initDynamicContent);
    observer.observe(contentArea, { childList: true, subtree: true });
  }

  // Register service worker for PWA if supported
  if ("serviceWorker" in navigator) {
    window.addEventListener("load", function () {
      navigator.serviceWorker.register("/service-worker.js").then(
        function (registration) {
          console.log(
            "ServiceWorker registration successful with scope: ",
            registration.scope
          );
        },
        function (err) {
          console.log("ServiceWorker registration failed: ", err);
        }
      );
    });
  }
};

// Run when the DOM is fully loaded
document.addEventListener("DOMContentLoaded", function () {
  // Initialize the application
  initApp();
});

// Utility function to format date
const formatDate = (date) => {
  const options = { year: "numeric", month: "short", day: "numeric" };
  return new Date(date).toLocaleDateString("id-ID", options);
};

// Utility function to format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
  }).format(amount);
};

// Utility function to generate random data for charts
const generateRandomData = (min, max, count) => {
  const data = [];
  for (let i = 0; i < count; i++) {
    data.push(Math.floor(Math.random() * (max - min + 1)) + min);
  }
  return data;
};

// Utility function to get color based on status
const getStatusColor = (status) => {
  const colors = {
    completed: "#55b687",
    "in-progress": "#f9d56c",
    pending: "#adb5bd",
    cancelled: "#e76f51",
  };
  return colors[status.toLowerCase()] || "#adb5bd";
};

// Export utilities for use in other modules
window.adminDash = {
  formatDate,
  formatCurrency,
  generateRandomData,
  getStatusColor,
};
/**
 * AdminDash - Custom Admin Dashboard
 * Main JavaScript file
 */

// Initialize tooltips
const initTooltips = () => {
  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
};

// Initialize popovers
const initPopovers = () => {
  const popoverTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="popover"]')
  );
  popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });
};

// Initialize dropdowns
const initDropdowns = () => {
  const dropdownElementList = [].slice.call(
    document.querySelectorAll(".dropdown-toggle")
  );
  dropdownElementList.map(function (dropdownToggleEl) {
    return new bootstrap.Dropdown(dropdownToggleEl);
  });
};

// Handle mobile navigation
const handleMobileNav = () => {
  const handleWindowResize = () => {
    if (window.innerWidth < 992) {
      document.body.classList.add("sidebar-mobile");
    } else {
      document.body.classList.remove("sidebar-mobile");
    }
  };

  // Run on initial load
  handleWindowResize();

  // Run on resize
  window.addEventListener("resize", handleWindowResize);
};

// Theme switcher functionality
const initThemeSwitcher = () => {
  const themeSwitcher = document.getElementById("themeSwitcher");

  if (themeSwitcher) {
    // Check for saved theme preference
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme) {
      document.documentElement.setAttribute("data-bs-theme", savedTheme);
      if (savedTheme === "dark") {
        themeSwitcher.checked = true;
      }
    }

    // Listen for theme changes
    themeSwitcher.addEventListener("change", function () {
      if (this.checked) {
        document.documentElement.setAttribute("data-bs-theme", "dark");
        localStorage.setItem("theme", "dark");
      } else {
        document.documentElement.setAttribute("data-bs-theme", "light");
        localStorage.setItem("theme", "light");
      }
    });
  }
};

// Save sidebar state in localStorage
const saveSidebarState = (collapsed) => {
  localStorage.setItem("sidebarCollapsed", collapsed);
};

// Load sidebar state from localStorage
const loadSidebarState = () => {
  const state = localStorage.getItem("sidebarCollapsed");
  return state === "true";
};

// Initialize dashboard charts
const initDashboardCharts = () => {
  // This will be handled by Alpine.js
};

// Initialize the application
const initApp = () => {
  // Initialize Bootstrap components
  initTooltips();
  initPopovers();
  initDropdowns();

  // Initialize app features
  handleMobileNav();
  initThemeSwitcher();

  // Initialize charts if on dashboard page
  if (window.location.hash === "#dashboard" || window.location.hash === "") {
    initDashboardCharts();
  }

  // Register service worker for PWA if supported
  if ("serviceWorker" in navigator) {
    window.addEventListener("load", function () {
      navigator.serviceWorker.register("/service-worker.js").then(
        function (registration) {
          console.log(
            "ServiceWorker registration successful with scope: ",
            registration.scope
          );
        },
        function (err) {
          console.log("ServiceWorker registration failed: ", err);
        }
      );
    });
  }
};

// Run when the DOM is fully loaded
document.addEventListener("DOMContentLoaded", function () {
  // Initialize the application
  initApp();
});

// Utility function to format date
const formatDate = (date) => {
  const options = { year: "numeric", month: "short", day: "numeric" };
  return new Date(date).toLocaleDateString("en-US", options);
};

// Utility function to format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount);
};

// Utility function to generate random data for charts
const generateRandomData = (min, max, count) => {
  const data = [];
  for (let i = 0; i < count; i++) {
    data.push(Math.floor(Math.random() * (max - min + 1)) + min);
  }
  return data;
};

// Utility function to get color based on status
const getStatusColor = (status) => {
  const colors = {
    completed: "#55b687",
    "in-progress": "#f9d56c",
    pending: "#adb5bd",
    cancelled: "#e76f51",
  };
  return colors[status.toLowerCase()] || "#adb5bd";
};

// Export utilities for use in other modules
window.adminDash = {
  formatDate,
  formatCurrency,
  generateRandomData,
  getStatusColor,
};
