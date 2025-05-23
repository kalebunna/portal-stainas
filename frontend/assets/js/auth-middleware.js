/**
 * Authentication Middleware
 * Handles authentication and authorization for the admin dashboard
 */

class AuthMiddleware {
  constructor() {
    console.log("AuthMiddleware initialized", this.token, this.user);

    // Public routes that don't require authentication
    this.publicRoutes = ["login", "forgot-password", "reset-password"];

    // Initialize
    this.init();
  }
  /**
   * Get current user data
   * @returns {object|null} User data
   */
  getUser() {
    return JSON.parse(localStorage.getItem("user") || "null");
  }

  /**
   * Get authentication token
   * @returns {string|null} Token
   */
  getToken() {
    return localStorage.getItem("auth_token");
  }

  /**
   * Get user roles
   * @returns {Array} Roles
   */
  getRoles() {
    return JSON.parse(localStorage.getItem("roles") || "[]");
  }

  /**
   * Get user permissions
   * @returns {Array} Permissions
   */
  getPermissions() {
    return JSON.parse(localStorage.getItem("permissions") || "[]");
  }

  /**
   * Check if user has a specific role
   * @param {string} roleName - Role to check
   * @returns {boolean} Whether user has the role
   */
  hasRole(roleName) {
    return this.getRoles().includes(roleName);
  }

  /**
   * Check if user has a specific permission
   * @param {string} permissionName - Permission to check
   * @returns {boolean} Whether user has the permission
   */
  hasPermission(permissionName) {
    console.log("Checking permission", permissionName, this.getPermissions());
    return this.getPermissions().includes(permissionName);
  }

  // ... (method lainnya tetap sama)

  /**
   * Initialize middleware
   */
  init() {
    // Listen for hash changes to check authentication
    window.addEventListener("hashchange", () => this.checkAuth());

    // Check authentication on initial load
    this.checkAuth();

    // Periodic token validation (every 5 minutes)
    setInterval(() => this.validateToken(), 5 * 60 * 1000);
  }

  /**
   * Check if user is authenticated
   * @returns {boolean} Authentication status
   */
  isAuthenticated() {
    const token = this.getToken();
    const user = this.getUser();
    console.log("Checking authentication status", token, user);
    return !!token && !!user;
  }

  /**
   * Check if current route is public
   * @returns {boolean} Whether current route is public
   */
  isPublicRoute() {
    const hash = window.location.hash.substring(1) || "dashboard";
    return this.publicRoutes.includes(hash);
  }

  /**
   * Get current route
   * @returns {string} Current route
   */
  getCurrentRoute() {
    return window.location.hash.substring(1) || "dashboard";
  }

  /**
   * Check if user has a specific role
   * @param {string} roleName - Role to check
   * @returns {boolean} Whether user has the role
   */
  hasRole(roleName) {
    return this.roles.includes(roleName);
  }

  /**
   * Check if user has a specific permission
   * @param {string} permissionName - Permission to check
   * @returns {boolean} Whether user has the permission
   */
  hasPermission(permissionName) {
    return this.permissions.includes(permissionName);
  }

  /**
   * Check authorization for the current route
   * Redirects to login if not authenticated
   */
  checkAuth() {
    const currentRoute = this.getCurrentRoute();

    // Skip check for public routes
    if (this.isPublicRoute()) {
      return;
    }

    // Force redirect to login if not authenticated
    if (!this.isAuthenticated()) {
      console.log("Not authenticated, redirecting to login");

      // Redirect to login
      window.location.hash = "login";

      // Show error message if coming from a protected route
      if (currentRoute !== "login") {
        if (window.appToast) {
          window.appToast.error("Please login to access the admin dashboard.");
        }
      }
      return;
    }
  }

  /**
   * Get all sidebar items based on user permissions
   * @returns {Array} Filtered sidebar items
   */
  getSidebarItems() {
    // Define all possible sidebar items with their permission requirements
    const allSidebarItems = [
      {
        title: "Program Studi",
        icon: "bi-book",
        route: "prodi",
        permissions: ["view-prodi"],
      },
      {
        title: "Dashboard",
        icon: "bi-grid-1x2-fill",
        route: "dashboard",
        permissions: [], // Everyone can access dashboard once logged in
      },
      {
        title: "News",
        icon: "bi-newspaper",
        route: "berita",
        permissions: ["view-berita"],
      },
      {
        title: "Announcements",
        icon: "bi-megaphone",
        route: "pengumuman",
        permissions: ["view-pengumuman"],
      },
      {
        title: "Calendar",
        icon: "bi-calendar-event",
        route: "agenda",
        permissions: ["view-agenda"],
      },
      {
        title: "Partnerships",
        icon: "bi-handshake",
        route: "kerjasama",
        permissions: ["view-kerjasama"],
      },
      {
        title: "Students",
        icon: "bi-mortarboard",
        route: "mahasiswa",
        permissions: ["view-mahasiswa"],
      },
      {
        title: "Student Works",
        icon: "bi-journal-richtext",
        route: "karya",
        permissions: ["view-karya"],
      },
      {
        title: "Media Library",
        icon: "bi-images",
        route: "media",
        permissions: ["manage-media"],
      },
      {
        title: "Settings",
        icon: "bi-gear",
        route: "settings",
        roles: ["admin"], // Only admins can access settings
      },
    ];

    // If not authenticated, return empty array
    if (!this.isAuthenticated()) {
      return [];
    }

    // Filter items based on user permissions
    return allSidebarItems.filter((item) => {
      // Check role-based access
      if (item.roles && item.roles.length > 0) {
        const hasRequiredRole = item.roles.some((role) => this.hasRole(role));
        if (!hasRequiredRole) return false;
      }

      // Check permission-based access
      if (item.permissions && item.permissions.length > 0) {
        const hasRequiredPermission = item.permissions.some((perm) =>
          this.hasPermission(perm)
        );
        if (!hasRequiredPermission) return false;
      }

      return true;
    });
  }

  /**
   * Log out user
   */
  logout() {
    return new Promise((resolve, reject) => {
      try {
        // Call logout API endpoint to invalidate token
        fetch("/api/logout", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${this.token}`,
          },
          credentials: "include",
        })
          .then(() => {
            // Clear local storage
            this._clearAuthData();

            // Redirect to login
            window.location.hash = "login";

            resolve();
          })
          .catch((error) => {
            console.error("Logout error:", error);

            // Still clear local storage and redirect on error
            this._clearAuthData();

            window.location.hash = "login";

            resolve();
          });
      } catch (error) {
        console.error("Logout error:", error);
        this._clearAuthData();
        window.location.hash = "login";
        reject(error);
      }
    });
  }

  /**
   * Clear authentication data
   * @private
   */
  _clearAuthData() {
    localStorage.removeItem("auth_token");
    localStorage.removeItem("user");
    localStorage.removeItem("roles");
    localStorage.removeItem("permissions");

    // Reset properties
    this.token = null;
    this.user = null;
    this.roles = [];
    this.permissions = [];
  }

  /**
   * Check token validity with the server
   * @returns {Promise<boolean>} Whether token is valid
   */
  async validateToken() {
    try {
      if (!this.token) {
        // No token, consider not authenticated
        this._clearAuthData();
        return false;
      }

      const response = await fetch("/api/validate-token", {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${this.token}`,
        },
        credentials: "include",
      });

      if (!response.ok) {
        // Token is invalid, clear auth data
        console.log("Token validation failed, clearing auth data");
        this._clearAuthData();

        // Redirect to login if on a protected page
        if (!this.isPublicRoute()) {
          window.location.hash = "login";

          if (window.appToast) {
            window.appToast.error(
              "Your session has expired. Please login again."
            );
          }
        }

        return false;
      }

      return true;
    } catch (error) {
      console.error("Token validation error:", error);

      // On error, consider token invalid
      this._clearAuthData();

      // Redirect if on protected page
      if (!this.isPublicRoute()) {
        window.location.hash = "login";
      }

      return false;
    }
  }
}

// Create and export auth middleware instance
const authMiddleware = new AuthMiddleware();
window.authMiddleware = authMiddleware;
