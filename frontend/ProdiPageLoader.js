/**
 * ProdiPageLoader.js
 * Handler for Program Studi pages navigation
 */
class ProdiPageLoader {
  constructor() {
    // Page settings
    this.pages = {
      list: "/pages/prodi/index.html",
      create: "/pages/prodi/form.html",
      edit: "/pages/prodi/form.html",
      detail: "/pages/prodi/detail.html",
    };
    this.defaultPage = "list";
    this.contentArea = document.querySelector(".content-area");
  }

  /**
   * Initialize the page loader
   */
  init() {
    // Listen for hash changes
    window.addEventListener("hashchange", () => this.handleNavigation());

    // Handle initial navigation
    this.handleNavigation();
  }

  /**
   * Parse hash URL to determine requested page
   * @returns {Object} Page information
   */
  parseHash() {
    // Get hash without the # character
    const hash = window.location.hash.substring(1);

    // Split into segments
    const segments = hash.split("/");

    // Check if this is a prodi page
    if (segments[0] !== "prodi") {
      return null;
    }

    // Determine page type and parameters
    let page = this.defaultPage;
    let params = {};

    if (segments.length >= 2) {
      if (this.pages[segments[1]]) {
        page = segments[1];
      }

      // Extract parameters for detail and edit
      if (segments.length >= 3) {
        if (page === "detail") {
          params.slug = segments[2];
        } else if (page === "edit") {
          params.id = segments[2];
        }
      }
    }

    return {
      page,
      params,
      url: this.pages[page] || this.pages[this.defaultPage],
    };
  }

  /**
   * Handle navigation changes
   */
  async handleNavigation() {
    // Parse the hash to determine requested page
    const pageInfo = this.parseHash();

    // If not a prodi page, do nothing
    if (!pageInfo) {
      return;
    }

    try {
      // Show loading state
      this.showLoading();

      // Fetch the page content
      const response = await fetch(pageInfo.url);

      if (!response.ok) {
        throw new Error(`Failed to load page: ${response.status}`);
      }

      // Get page content
      const content = await response.text();

      // Update content area
      if (this.contentArea) {
        this.contentArea.innerHTML = content;

        // Initialize Alpine components
        if (window.Alpine) {
          window.Alpine.initTree(this.contentArea);
        }
      }
    } catch (error) {
      console.error("Error loading page:", error);
      this.showError(error.message);
    }
  }

  /**
   * Show loading state
   */
  showLoading() {
    if (this.contentArea) {
      this.contentArea.innerHTML = `
          <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Memuat halaman...</p>
          </div>
        `;
    }
  }

  /**
   * Show error state
   * @param {string} message - Error message to display
   */
  showError(message) {
    if (this.contentArea) {
      this.contentArea.innerHTML = `
          <div class="p-4 text-center">
            <div class="mb-3">
              <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 3rem;"></i>
            </div>
            <h5 class="text-danger">Gagal Memuat Halaman</h5>
            <p class="text-muted">${
              message || "Terjadi kesalahan, silakan coba lagi"
            }</p>
            <button onclick="window.location.reload()" class="btn btn-primary">
              <i class="bi bi-arrow-clockwise me-2"></i> Muat Ulang
            </button>
          </div>
        `;
    }
  }
}

// Initialize the page loader when document is ready
document.addEventListener("DOMContentLoaded", () => {
  const prodiPageLoader = new ProdiPageLoader();
  prodiPageLoader.init();

  // Make it available globally if needed
  window.prodiPageLoader = prodiPageLoader;
});
