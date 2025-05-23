// Get API configuration from global config or use default
const API_CONFIG = window.AppConfig?.api || {
  baseUrl: "http://localhost:8000",
  version: "api",
  auth: {
    login: "/login",
    logout: "/logout",
    validateToken: "/validate-token",
    forgotPassword: "/forgot-password",
    resetPassword: "/reset-password",
  },
};

// Build API base URL from configuration
const API_BASE_URL = `${API_CONFIG.baseUrl}/${API_CONFIG.version}`;

class AuthApiService {
  constructor() {
    this.token = localStorage.getItem("auth_token");
    console.log("Auth Service initialized", { token: !!this.token });
  }

  /**
   * Get authentication headers
   * @returns {Object} Headers object with Authorization token
   */
  getHeaders() {
    const headers = {
      "Content-Type": "application/json",
      Accept: "application/json",
    };

    if (this.token) {
      headers["Authorization"] = `Bearer ${this.token}`;
    }

    // Get CSRF token for Laravel Sanctum if needed
    const csrfToken = document
      .querySelector('meta[name="csrf-token"]')
      ?.getAttribute("content");
    if (csrfToken) {
      headers["X-CSRF-TOKEN"] = csrfToken;
    }

    return headers;
  }

  /**
   * Handle API response
   * @param {Response} response - Fetch response object
   * @returns {Promise} JSON data or error
   */
  async handleResponse(response) {
    // Log response status
    console.log(`API Response Status: ${response.status}`);

    const contentType = response.headers.get("content-type");

    // Check if response is JSON
    if (contentType && contentType.includes("application/json")) {
      const data = await response.json();

      if (!response.ok) {
        // Handle 401 Unauthorized (token expired or invalid)
        if (response.status === 401) {
          this.clearAuth();

          // Redirect to login if not already there
          if (window.location.hash !== "#login") {
            window.location.hash = "login";

            // Show error message
            if (window.appToast) {
              window.appToast.error(
                "Your session has expired. Please login again."
              );
            }
          }
        }

        throw new Error(
          data.message || `HTTP error! status: ${response.status}`
        );
      }

      return data;
    } else {
      // Handle non-JSON responses
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      return await response.text();
    }
  }

  /**
   * Make API request
   * @param {string} endpoint - API endpoint
   * @param {string} method - HTTP method (GET, POST, PUT, DELETE)
   * @param {Object} data - Request body data
   * @returns {Promise} Response data
   */
  async request(endpoint, method = "GET", data = null) {
    const url = `${API_BASE_URL}${endpoint}`;

    // Log request details
    console.log(`API Request: ${method} ${url}`, { data });

    const options = {
      method,
      headers: this.getHeaders(),
      //   credentials: "include", // Include cookies for cross-site requests if needed
    };

    if (data && ["POST", "PUT", "PATCH"].includes(method)) {
      options.body = JSON.stringify(data);
    }

    try {
      const response = await fetch(url, options);
      return await this.handleResponse(response);
    } catch (error) {
      console.error(`API request failed for ${endpoint}:`, error);
      throw error;
    }
  }

  /**
   * Clear authentication data
   */
  clearAuth() {
    this.token = null;
    localStorage.removeItem("auth_token");
    localStorage.removeItem("user");
    localStorage.removeItem("roles");
    localStorage.removeItem("permissions");
    console.log("Auth data cleared");
  }

  /**
   * Login user
   * @param {string} email - User email
   * @param {string} password - User password
   * @param {boolean} remember - Remember me
   * @returns {Promise} User data and token
   */
  async login(email, password, remember = false) {
    console.log(`Login request for: ${email}`);

    // Make API request to login endpoint
    const data = await this.request(API_CONFIG.auth.login, "POST", {
      email,
      password,
      remember,
    });

    console.log("Login successful", { hasToken: !!data.access_token });
    console.log("Login data:", data.permissions);
    // Store authentication data
    if (data.access_token) {
      this.token = data.access_token;
      localStorage.setItem("auth_token", data.access_token);

      // Store user data
      if (data.user) {
        localStorage.setItem("user", JSON.stringify(data.user));
      }

      // Store roles and permissions
      if (data.roles) {
        localStorage.setItem("roles", JSON.stringify(data.roles));
      }

      if (data.permissions) {
        localStorage.setItem("permissions", JSON.stringify(data.permissions));
      }
    }

    return data;
  }

  /**
   * Logout user
   * @returns {Promise} Logout result
   */
  async logout() {
    try {
      // Call API for logout
      await this.request(API_CONFIG.auth.logout, "POST");
      console.log("Logout API call successful");
    } catch (error) {
      console.error("Logout API error:", error);
    } finally {
      // Always clear auth data regardless of API response
      this.clearAuth();
    }
  }

  /**
   * Get current user profile
   * @returns {Promise} User data
   */
  async getProfile() {
    return await this.request("/user");
  }

  /**
   * Update user profile
   * @param {Object} userData - Updated user data
   * @returns {Promise} Updated user
   */
  async updateProfile(userData) {
    const data = await this.request("/user", "PUT", userData);

    // Update stored user data
    if (data.user) {
      localStorage.setItem("user", JSON.stringify(data.user));
    }

    return data;
  }

  /**
   * Change user password
   * @param {string} currentPassword - Current password
   * @param {string} newPassword - New password
   * @param {string} confirmPassword - Confirm new password
   * @returns {Promise} Result
   */
  async changePassword(currentPassword, newPassword, confirmPassword) {
    return await this.request("/user/password", "PUT", {
      current_password: currentPassword,
      password: newPassword,
      password_confirmation: confirmPassword,
    });
  }

  /**
   * Request password reset
   * @param {string} email - User email
   * @returns {Promise} Result
   */
  async forgotPassword(email) {
    return await this.request(API_CONFIG.auth.forgotPassword, "POST", {
      email,
    });
  }

  /**
   * Reset password
   * @param {string} email - User email
   * @param {string} password - New password
   * @param {string} passwordConfirmation - Confirm new password
   * @param {string} token - Reset token
   * @returns {Promise} Result
   */
  async resetPassword(email, password, passwordConfirmation, token) {
    return await this.request(API_CONFIG.auth.resetPassword, "POST", {
      email,
      password,
      password_confirmation: passwordConfirmation,
      token,
    });
  }

  /**
   * Validate authentication token
   * @returns {Promise<boolean>} Whether token is valid
   */
  async validateToken() {
    try {
      if (!this.token) return false;

      const data = await this.request(API_CONFIG.auth.validateToken);
      return true;
    } catch (error) {
      console.warn("Token validation failed:", error.message);
      return false;
    }
  }
}

// Create and export instance
const authApiService = new AuthApiService();
window.authApiService = authApiService;

// Log that service is available
console.log("Auth API Service registered", { service: authApiService });
