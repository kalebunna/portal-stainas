const AppConfig = {
  // API Configuration
  api: {
    // Base URL for the API
    baseUrl: "https://api.yourdomain.com",

    // API Version
    version: "v1",

    // Full API URL with version
    url: function () {
      return `${this.baseUrl}/${this.version}`;
    },

    // Authentication endpoints
    auth: {
      login: "/auth/login",
      logout: "/auth/logout",
      register: "/auth/register",
      forgotPassword: "/auth/forgot-password",
      resetPassword: "/auth/reset-password",
      validateToken: "/auth/validate-token",
    },

    // Content management endpoints
    content: {
      berita: "/berita",
      pengumuman: "/pengumuman",
      agenda: "/agenda",
      media: "/media",
    },

    // User management endpoints
    users: {
      profile: "/users/profile",
      permissions: "/users/permissions",
      roles: "/users/roles",
    },
  },

  // Pagination defaults
  pagination: {
    itemsPerPage: 10,
    maxPagesToShow: 5,
  },

  // Toast notification defaults
  toast: {
    defaultDuration: 3000,
    positions: {
      default: "bottom-right",
      alert: "top-center",
    },
  },

  // Feature flags
  features: {
    darkMode: true,
    notifications: true,
    pwa: true,
  },
};

// Make configuration available globally
window.AppConfig = AppConfig;

// Export for module usage
export default AppConfig;
