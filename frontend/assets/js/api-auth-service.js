class AuthApiService {
  constructor() {
    this.token = localStorage.getItem("auth_token");
    console.log("Auth Service initialized");
  }

  /**
   * Login user
   * @param {string} email - User email
   * @param {string} password - User password
   * @param {boolean} remember - Remember me
   * @returns {Promise} User data and token
   */
  async login(email, password, remember = false) {
    // For development purposes only - in production this would call your real API
    console.log(`Simulating login for ${email}`);

    // Basic validation
    if (!email || !password) {
      throw new Error("Email and password are required");
    }

    // In development environment, accept any credentials
    // In production, you would validate with real API
    const mockResponse = {
      token: "dev-token-" + Date.now(),
      user: {
        id: 1,
        name: email.split("@")[0] || "User",
        email: email,
      },
      roles: ["admin"],
      permissions: [
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
      ],
    };

    // Simulate API delay
    await new Promise((resolve) => setTimeout(resolve, 800));

    // Store authentication data
    this.token = mockResponse.token;
    localStorage.setItem("auth_token", mockResponse.token);
    localStorage.setItem("user", JSON.stringify(mockResponse.user));
    localStorage.setItem("roles", JSON.stringify(mockResponse.roles));
    localStorage.setItem(
      "permissions",
      JSON.stringify(mockResponse.permissions)
    );

    return mockResponse;
  }

  /**
   * Logout user
   * @returns {Promise} Logout result
   */
  async logout() {
    // Simulate API delay
    await new Promise((resolve) => setTimeout(resolve, 500));

    // Clear auth data
    localStorage.removeItem("auth_token");
    localStorage.removeItem("user");
    localStorage.removeItem("roles");
    localStorage.removeItem("permissions");
    this.token = null;

    return { success: true, message: "Logged out successfully" };
  }

  /**
   * Get current user profile
   * @returns {Promise} User data
   */
  async getProfile() {
    if (!this.token) {
      throw new Error("Not authenticated");
    }

    // Return mock user data from localStorage
    return {
      user: JSON.parse(localStorage.getItem("user") || "null"),
    };
  }

  /**
   * Validate authentication token
   * @returns {Promise<boolean>} Whether token is valid
   */
  async validateToken() {
    // For development, consider all tokens valid
    return !!this.token;
  }

  /**
   * Request password reset
   * @param {string} email - User email
   * @returns {Promise} Result
   */
  async forgotPassword(email) {
    // Simulate API delay
    await new Promise((resolve) => setTimeout(resolve, 1000));

    return {
      success: true,
      message: "Password reset instructions sent to your email",
    };
  }
}

// Create and export instance
const authApiService = new AuthApiService();
window.authApiService = authApiService;

// Legacy export
if (typeof module !== "undefined" && module.exports) {
  module.exports = authApiService;
}
