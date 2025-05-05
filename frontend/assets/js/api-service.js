/**
 * API Service - Handles all interactions with the backend API
 */

const API_BASE_URL = "https://api.example.com/v1"; // Replace with your actual API URL

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

  // User-related endpoints

  /**
   * Authenticate user
   * @param {string} email - User email
   * @param {string} password - User password
   * @returns {Promise} - Resolved with user data and token
   */
  async login(email, password) {
    const data = await this.request("/auth/login", "POST", { email, password });
    this.setToken(data.token);
    return data.user;
  }

  /**
   * Get current user profile
   * @returns {Promise} - Resolved with user data
   */
  async getCurrentUser() {
    return await this.request("/users/me");
  }

  // Project-related endpoints

  /**
   * Get all projects
   * @returns {Promise} - Resolved with projects array
   */
  async getProjects() {
    return await this.request("/projects");
  }

  /**
   * Get project by ID
   * @param {string} id - Project ID
   * @returns {Promise} - Resolved with project data
   */
  async getProject(id) {
    return await this.request(`/projects/${id}`);
  }

  /**
   * Create new project
   * @param {Object} project - Project data
   * @returns {Promise} - Resolved with created project
   */
  async createProject(project) {
    return await this.request("/projects", "POST", project);
  }

  /**
   * Update project
   * @param {string} id - Project ID
   * @param {Object} data - Updated project data
   * @returns {Promise} - Resolved with updated project
   */
  async updateProject(id, data) {
    return await this.request(`/projects/${id}`, "PUT", data);
  }

  /**
   * Delete project
   * @param {string} id - Project ID
   * @returns {Promise} - Resolved with success message
   */
  async deleteProject(id) {
    return await this.request(`/projects/${id}`, "DELETE");
  }

  // Task-related endpoints

  /**
   * Get all tasks
   * @param {Object} filters - Optional filters
   * @returns {Promise} - Resolved with tasks array
   */
  async getTasks(filters = {}) {
    let endpoint = "/tasks";

    // Build query string from filters
    if (Object.keys(filters).length > 0) {
      const params = new URLSearchParams();
      for (const key in filters) {
        if (filters[key]) {
          params.append(key, filters[key]);
        }
      }
      endpoint += `?${params.toString()}`;
    }

    return await this.request(endpoint);
  }

  /**
   * Get task by ID
   * @param {string} id - Task ID
   * @returns {Promise} - Resolved with task data
   */
  async getTask(id) {
    return await this.request(`/tasks/${id}`);
  }

  /**
   * Create new task
   * @param {Object} task - Task data
   * @returns {Promise} - Resolved with created task
   */
  async createTask(task) {
    return await this.request("/tasks", "POST", task);
  }

  /**
   * Update task
   * @param {string} id - Task ID
   * @param {Object} data - Updated task data
   * @returns {Promise} - Resolved with updated task
   */
  async updateTask(id, data) {
    return await this.request(`/tasks/${id}`, "PUT", data);
  }

  /**
   * Delete task
   * @param {string} id - Task ID
   * @returns {Promise} - Resolved with success message
   */
  async deleteTask(id) {
    return await this.request(`/tasks/${id}`, "DELETE");
  }

  // Team-related endpoints

  /**
   * Get all team members
   * @returns {Promise} - Resolved with team members array
   */
  async getTeamMembers() {
    return await this.request("/team");
  }

  /**
   * Get team member by ID
   * @param {string} id - Team member ID
   * @returns {Promise} - Resolved with team member data
   */
  async getTeamMember(id) {
    return await this.request(`/team/${id}`);
  }

  /**
   * Invite team member
   * @param {Object} data - Invitation data
   * @returns {Promise} - Resolved with success message
   */
  async inviteTeamMember(data) {
    return await this.request("/team/invite", "POST", data);
  }

  // Analytics endpoints

  /**
   * Get dashboard stats
   * @returns {Promise} - Resolved with dashboard statistics
   */
  async getDashboardStats() {
    return await this.request("/analytics/dashboard");
  }

  /**
   * Get project analytics
   * @param {string} projectId - Project ID
   * @param {string} timeframe - Timeframe (week, month, year)
   * @returns {Promise} - Resolved with project analytics
   */
  async getProjectAnalytics(projectId, timeframe = "month") {
    return await this.request(
      `/analytics/projects/${projectId}?timeframe=${timeframe}`
    );
  }

  /**
   * Get team performance
   * @param {string} timeframe - Timeframe (week, month, year)
   * @returns {Promise} - Resolved with team performance data
   */
  async getTeamPerformance(timeframe = "month") {
    return await this.request(`/analytics/team?timeframe=${timeframe}`);
  }
}

// Export an instance of the API service
const apiService = new ApiService();
window.apiService = apiService;

export default apiService;
