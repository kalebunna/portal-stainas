// import AppConfig from './config.js';

// Use configuration object
const API_CONFIG = window.AppConfig
  ? window.AppConfig.api
  : {
      baseUrl: "http://127.0.0.1:8000/api",
      version: "v1",
    };

// Build API base URL from configuration
const API_BASE_URL = `${API_CONFIG.baseUrl}/${API_CONFIG.version}`;

class ApiService {
  constructor() {
    this.token = localStorage.getItem("auth_token");
  }

  /**
   * Set authentication token for future requests
   * @param {string} token - JWT token from authentication
   */
  setToken(token) {
    this.token = token;
    localStorage.setItem("auth_token", token);
  }

  /**
   * Clear authentication token and logout
   */
  clearToken() {
    this.token = null;
    localStorage.removeItem("auth_token");
  }

  /**
   * Get authentication headers
   * @returns {Object} - Headers object
   */
  getHeaders() {
    const headers = {
      "Content-Type": "application/json",
    };

    if (this.token) {
      headers["Authorization"] = `Bearer ${this.token}`;
    }

    return headers;
  }

  /**
   * Handle API response
   * @param {Response} response - Fetch API response
   * @returns {Promise} - Resolved promise with data or rejected with error
   */
  async handleResponse(response) {
    const data = await response.json();

    if (!response.ok) {
      // Check if token has expired
      if (response.status === 401) {
        this.clearToken();
        // Redirect to login
        window.location.href = "#login";

        // Show message using toast if available
        if (window.appToast) {
          window.appToast.error(
            "Your session has expired. Please login again."
          );
        }
      }

      // Throw error with message from API
      throw new Error(data.message || "An error occurred");
    }

    return data;
  }

  /**
   * Make authenticated API request
   * @param {string} endpoint - API endpoint
   * @param {string} method - HTTP method
   * @param {Object} data - Request payload
   * @returns {Promise} - Resolved with API response
   */
  async request(endpoint, method = "GET", data = null) {
    try {
      const url = `${API_BASE_URL}${endpoint}`;
      const options = {
        method,
        headers: this.getHeaders(),
      };

      if (
        data &&
        (method === "POST" || method === "PUT" || method === "PATCH")
      ) {
        options.body = JSON.stringify(data);
      }

      const response = await fetch(url, options);
      return await this.handleResponse(response);
    } catch (error) {
      console.error("API request failed:", error);
      throw error;
    }
  }
}

// Export an instance of the API service
const apiService = new ApiService();
window.apiService = apiService;
