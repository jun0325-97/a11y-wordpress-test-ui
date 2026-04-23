(function () {
  ("use strict");
  const { __ } = wp.i18n;
  window.a11y = window.a11y || {
    postsPerPage: 1,
    lastPostId: 0,
    intervals: {},
    redirectUrl: "",
    isProcessing: false,
    retryCount: 0,
    maxRetries: 2,
    progressCurrent: 0,
    progressSuccessful: 0,
    progressSkipped: 0,
    progressMax: 0,
  };

  // Utility function to ensure progress state consistency
  window.a11y.validateProgressState = function () {
    this.progressCurrent = isNaN(this.progressCurrent)
      ? 0
      : Math.max(0, parseInt(this.progressCurrent, 10));
    this.progressSuccessful = isNaN(this.progressSuccessful)
      ? 0
      : Math.max(0, parseInt(this.progressSuccessful, 10));
    this.progressSkipped = isNaN(this.progressSkipped)
      ? 0
      : Math.max(0, parseInt(this.progressSkipped, 10));
    this.progressMax = isNaN(this.progressMax)
      ? 100
      : Math.max(1, parseInt(this.progressMax, 10));
    this.lastPostId = isNaN(this.lastPostId)
      ? 0
      : Math.max(0, parseInt(this.lastPostId, 10));
    this.retryCount = isNaN(this.retryCount)
      ? 0
      : Math.max(0, parseInt(this.retryCount, 10));
  };

  /**
   * Safely calculates percentage, preventing NaN and infinity.
   * @param {number} current - Current progress value
   * @param {number} max - Maximum progress value
   * @returns {number} Percentage (0-100), or 0 if calculation invalid
   */
  window.a11y.safePercentage = function (current, max) {
    const curr = parseInt(current, 10);
    const total = parseInt(max, 10);

    // Guard against invalid inputs
    if (isNaN(curr) || isNaN(total) || total <= 0) {
      return 0;
    }

    const percentage = (curr * 100) / total;

    // Clamp to valid range
    return Math.min(100, Math.max(0, percentage));
  };

  // Single function to manage Start Over button visibility
  window.a11y.updateStartOverButtonVisibility = function () {
    const staticStartOverButton = jQuery("#a11y-static-start-over-button");
    const hasSession =
      localStorage.getItem("a11y_bulk_progress") || this.isContinuation;
    const isProcessing = this.isProcessing;

    // During bulk processing, hide the button even if processing state fluctuates between batches
    const isBulkRunning =
      hasSession && this.progressCurrent > 0 && isProcessing;

    // Only show static Start Over button if there's a session AND not actively processing
    if (isBulkRunning || !hasSession) {
      staticStartOverButton.hide();
    } else {
      staticStartOverButton.show();
    }
  };

  // UI state management for processing
  window.a11y.setProcessingState = function (isProcessing) {
    this.isProcessing = isProcessing;
  };

  // Memory cleanup function
  window.a11y.cleanup = function () {
    // Clear intervals to prevent memory leaks
    if (this.intervals && typeof this.intervals === "object") {
      Object.values(this.intervals).forEach((intervalId) => {
        if (intervalId) clearInterval(intervalId);
      });
      this.intervals = {};
    }

    // Clear large objects
    if (this.errorHistory && this.errorHistory.length > 3) {
      this.errorHistory = this.errorHistory.slice(-3);
    }

    // Reset processing state and UI
    this.setProcessingState(false);
  };

  // Utility functions for button visibility management
  window.a11y.hideButtons = function () {
    jQuery("[data-bulk-generate-start]").addClass("a11y-hidden");
  };

  window.a11y.showButtons = function () {
    jQuery("[data-bulk-generate-start]").removeClass("a11y-hidden");
  };

  // Check if current URL parameters conflict with saved recovery session
  function hasUrlParameterConflicts(progress) {
    const urlParams = new URLSearchParams(window.location.search);

    // Check for bulk-select mode conflicts
    const currentAction = urlParams.get("a11y_action");
    const currentBatchId = urlParams.get("a11y_batch_id");
    const isBulkSelectUrl = currentAction === "bulk-select-generate";
    const isBulkSelectSession = progress.mode === "bulk-select";

    // If URL is bulk-select but session is not, or vice versa, it's a conflict
    if (isBulkSelectUrl !== isBulkSelectSession) {
      return true;
    }

    // If both are bulk-select but batch IDs don't match, it's a conflict
    if (isBulkSelectUrl && isBulkSelectSession) {
      if (
        currentBatchId &&
        progress.batchId &&
        currentBatchId !== progress.batchId
      ) {
        return true;
      }
    }

    // Check each setting that could be changed via URL parameters (for normal mode)
    if (!isBulkSelectUrl) {
      if (urlParams.get("a11y_mode") === "all" && progress.mode !== "all")
        return true;
      if (
        urlParams.get("a11y_attached") === "1" &&
        progress.onlyAttached !== "1"
      )
        return true;
      if (
        urlParams.get("a11y_attached") === "0" &&
        progress.onlyAttached === "1"
      )
        return true;
      if (urlParams.get("a11y_only_new") === "1" && progress.onlyNew !== "1")
        return true;
      if (urlParams.get("a11y_only_new") === "0" && progress.onlyNew === "1")
        return true;
      if (
        urlParams.get("a11y_wc_products") === "1" &&
        progress.wcProducts !== "1"
      )
        return true;
      if (
        urlParams.get("a11y_wc_products") === "0" &&
        progress.wcProducts === "1"
      )
        return true;
      if (
        urlParams.get("a11y_wc_only_featured") === "1" &&
        progress.wcOnlyFeatured !== "1"
      )
        return true;
      if (
        urlParams.get("a11y_wc_only_featured") === "0" &&
        progress.wcOnlyFeatured === "1"
      )
        return true;
    }

    return false;
  }

  // Consolidated session recovery function - runs on DOM ready
  function handleSessionRecovery() {
    try {
      const savedProgress = localStorage.getItem("a11y_bulk_progress");

      if (!savedProgress) {
        window.a11y.updateStartOverButtonVisibility();
        return;
      }

      const progress = JSON.parse(savedProgress);

      // Check if URL parameters conflict with saved session
      if (hasUrlParameterConflicts(progress)) {
        // Special handling for bulk-select sessions on wrong page
        if (progress.mode === "bulk-select" && progress.batchId) {
          // Show helpful message instead of just clearing
          const bulkSelectUrl =
            "admin.php?page=a11y-bulk-generate&a11y_action=bulk-select-generate&a11y_batch_id=" +
            progress.batchId;

          const banner = jQuery(`
            <div class="border bg-gray-900/5 p-px rounded-lg mb-6 a11y-bulk-select-notice">
              <div class="overflow-hidden rounded-lg bg-white">
                <div class="border-b border-gray-200 bg-white px-4 pt-5 pb-2 sm:px-6">
                  <h3 class="text-base font-semibold text-gray-900 my-0">Unfinished Bulk Selection</h3>
                </div>
                <div class="px-4 pb-4 sm:px-6">
                  <p class="text-sm text-gray-700 mb-0">
                    You have an unfinished bulk generation session from the Media Library with <strong>${
                      progress.progressCurrent || 0
                    } of ${progress.progressMax || 0} images processed</strong>.
                  </p>
                  <div class="mt-4 flex gap-3">
                    <a href="${bulkSelectUrl}" class="a11y-button blue no-underline">
                      Continue Processing
                    </a>
                    <button type="button" class="a11y-button black" onclick="localStorage.removeItem('a11y_bulk_progress'); localStorage.removeItem('a11y_error_history'); jQuery('.a11y-bulk-select-notice').remove();">
                      Discard Session
                    </button>
                  </div>
                </div>
              </div>
            </div>
          `);

          jQuery("#bulk-generate-form").prepend(banner);

          return;
        }

        localStorage.removeItem("a11y_bulk_progress");
        localStorage.removeItem("a11y_error_history");
        window.a11y.updateStartOverButtonVisibility();
        return;
      }

      // Set session state
      window.a11y.lastPostId = progress.lastPostId || 0;
      window.a11y.hasRecoveredSession = true;
      window.a11y.isContinuation = true;

      // Restore processing settings
      if (progress.mode) window.a11y.bulkGenerateMode = progress.mode;
      if (progress.batchId) window.a11y.bulkGenerateBatchId = progress.batchId;
      if (progress.onlyAttached)
        window.a11y.bulkGenerateOnlyAttached = progress.onlyAttached;
      if (progress.onlyNew) window.a11y.bulkGenerateOnlyNew = progress.onlyNew;
      if (progress.wcProducts)
        window.a11y.bulkGenerateWCProducts = progress.wcProducts;
      if (progress.wcOnlyFeatured)
        window.a11y.bulkGenerateWCOnlyFeatured = progress.wcOnlyFeatured;
      if (progress.keywords)
        window.a11y.bulkGenerateKeywords = progress.keywords;
      if (progress.negativeKeywords)
        window.a11y.bulkGenerateNegativeKeywords = progress.negativeKeywords;

      // Restore progress state with defensive defaults
      window.a11y.progressCurrent = Math.max(
        0,
        parseInt(progress.progressCurrent, 10) || 0
      );
      window.a11y.progressSuccessful = Math.max(
        0,
        parseInt(progress.progressSuccessful, 10) || 0
      );
      window.a11y.progressSkipped = Math.max(
        0,
        parseInt(progress.progressSkipped, 10) || 0
      );
      window.a11y.progressMax = Math.max(
        1,
        parseInt(progress.progressMax, 10) || 0
      );

      // If progressMax is still invalid, try to get it from the DOM
      if (window.a11y.progressMax <= 1) {
        const maxFromDOM = jQuery("[data-bulk-generate-progress-bar]").data(
          "max"
        );
        if (maxFromDOM && maxFromDOM > 0) {
          window.a11y.progressMax = Math.max(1, parseInt(maxFromDOM, 10));
        }
      }

      // Restore form settings
      if (progress.mode === "all") {
        jQuery("[data-bulk-generate-mode-all]").prop("checked", true);
      }
      if (progress.onlyAttached === "1") {
        jQuery("[data-bulk-generate-only-attached]").prop("checked", true);
      }
      if (progress.onlyNew === "1") {
        jQuery("[data-bulk-generate-only-new]").prop("checked", true);
      }
      if (progress.wcProducts === "1") {
        jQuery("[data-bulk-generate-wc-products]").prop("checked", true);
      }
      if (progress.wcOnlyFeatured === "1") {
        jQuery("[data-bulk-generate-wc-only-featured]").prop("checked", true);
      }
      if (progress.keywords && progress.keywords.length > 0) {
        jQuery("[data-bulk-generate-keywords]").val(
          progress.keywords.join(", ")
        );
      }
      if (progress.negativeKeywords && progress.negativeKeywords.length > 0) {
        jQuery("[data-bulk-generate-negative-keywords]").val(
          progress.negativeKeywords.join(", ")
        );
      }

      // Update button text and enable it
      const buttonEl = jQuery("[data-bulk-generate-start]");
      if (buttonEl.length) {
        const processed = progress.progressCurrent || 0;
        const total = progress.progressMax || 0;
        const remaining = Math.max(0, total - processed);

        if (remaining > 0) {
          const newText = __(
            "Continue: %d remaining images",
            "a11y-alt-text"
          ).replace("%d", remaining);
          buttonEl.text(newText);

          // Enable the button
          buttonEl
            .prop("disabled", false)
            .removeAttr("disabled")
            .removeClass("disabled")
            .addClass("blue")
            .removeAttr("style");
        }
      }

      // Show recovery notification banner
      jQuery(".a11y-recovery-banner").remove();
      showRecoveryNotification(progress);

      // Update progress display elements
      if (window.a11y.progressMaxEl && window.a11y.progressMaxEl.length) {
        window.a11y.progressMaxEl.text(window.a11y.progressMax);
      }
      if (
        window.a11y.progressCurrentEl &&
        window.a11y.progressCurrentEl.length
      ) {
        window.a11y.progressCurrentEl.text(window.a11y.progressCurrent);
      }
      if (
        window.a11y.progressSuccessfulEl &&
        window.a11y.progressSuccessfulEl.length
      ) {
        window.a11y.progressSuccessfulEl.text(window.a11y.progressSuccessful);
      }

      // Update Start Over button visibility
      window.a11y.updateStartOverButtonVisibility();
    } catch (e) {
      // If localStorage is corrupted, clear it
      localStorage.removeItem("a11y_bulk_progress");
      localStorage.removeItem("a11y_error_history");
      window.a11y.updateStartOverButtonVisibility();
    }
  }

  // Initialize session recovery when DOM is ready
  jQuery(document).ready(function () {
    // Initialize DOM element references first so they're available during session recovery
    window.a11y.progressBarEl = jQuery("[data-bulk-generate-progress-bar]");
    window.a11y.progressMaxEl = jQuery("[data-bulk-generate-progress-max]");
    window.a11y.progressCurrentEl = jQuery(
      "[data-bulk-generate-progress-current]"
    );
    window.a11y.progressSuccessfulEl = jQuery(
      "[data-bulk-generate-progress-successful]"
    );

    // Then handle session recovery
    handleSessionRecovery();
  });

  function showRecoveryNotification(progress) {
    // Prevent multiple banners
    if (window.a11y.recoveryBannerShown) {
      return;
    }
    window.a11y.recoveryBannerShown = true;

    const timeSince = Math.round((Date.now() - progress.timestamp) / 1000 / 60); // minutes
    const baseMessage =
      timeSince < 5
        ? __(
            "Previous bulk processing session found. The form has been restored to continue where you left off.",
            "a11y-alt-text"
          )
        : __(
            "Previous bulk processing session found from %d minutes ago. The form has been restored to continue where you left off.",
            "a11y-alt-text"
          ).replace("%d", timeSince);

    const resumeMessage =
      progress.lastPostId > 0
        ? __(
            " Processing will resume after image ID %d.",
            "a11y-alt-text"
          ).replace("%d", progress.lastPostId)
        : "";

    const message = baseMessage + resumeMessage;

    // Create a clean notification banner with Start Over button
    const banner = jQuery(`
      <div class="border bg-gray-900/5 p-px rounded-lg mb-6 a11y-recovery-banner">
        <div class="overflow-hidden rounded-lg bg-white">
          <div class="border-b border-gray-200 bg-white px-4 pt-5 pb-2 sm:px-6">
            <h3 class="text-base font-semibold text-gray-900 my-0">Previous Bulk Processing Session Found</h3>
          </div>
          <div class="px-4 pb-4 sm:px-6">
            <p class="text-sm text-gray-700 mb-0">
              ${message}
            </p>
            <div class="mt-4 flex gap-3">
              <button type="button" class="a11y-button blue" data-bulk-generate-start>
                Continue Processing
              </button>
              <button type="button" class="a11y-button black" id="a11y-banner-start-over-button">
                ${__("Start Over", "a11y-alt-text")}
              </button>
            </div>
          </div>
        </div>
      </div>
    `);

    // Insert banner at the top of the bulk generate form
    jQuery("#bulk-generate-form").prepend(banner);

    // Handle Start Over button click using document delegation for dynamic content
    jQuery(document).on(
      "click",
      ".a11y-recovery-banner #a11y-banner-start-over-button",
      function () {
        try {
          localStorage.removeItem("a11y_bulk_progress");
          localStorage.removeItem("a11y_error_history");

          // Complete memory cleanup
          window.a11y.cleanup();

          // Reset all window.a11y state
          window.a11y.lastPostId = 0;
          window.a11y.hasRecoveredSession = false;
          window.a11y.isContinuation = false;
          window.a11y.progressCurrent = 0;
          window.a11y.progressSuccessful = 0;
          window.a11y.progressSkipped = 0;
          window.a11y.retryCount = 0;

          // Reset processing UI state
          window.a11y.setProcessingState(false);

          // Remove the recovery banner
          jQuery(".a11y-recovery-banner").remove();

          // Restore original button text
          const buttonEl = jQuery("[data-bulk-generate-start]");
          if (buttonEl.length) {
            const defaultText =
              buttonEl.data("default-text") ||
              __("Generate Alt Text", "a11y-alt-text");
            buttonEl.text(defaultText);
            buttonEl.removeClass("disabled").prop("disabled", false);

            // Ensure button styling is also reset
            buttonEl.css({
              "background-color": "",
              color: "",
              "border-color": "",
            });
          }
        } catch (e) {
          console.error("A11Y: Error clearing recovery session:", e);
        }
      }
    );

    // Handle dismiss button (WordPress standard) - clear localStorage when dismissed
    banner.on("click", ".notice-dismiss", function () {
      try {
        localStorage.removeItem("a11y_bulk_progress");

        // Reset continuation flag so main button works normally
        window.a11y.lastPostId = 0;
        window.a11y.hasRecoveredSession = false;
        window.a11y.isContinuation = false;
        window.a11y.progressCurrent = 0;
        window.a11y.progressSuccessful = 0;
        window.a11y.retryCount = 0;

        // Restore original button text
        const buttonEl = jQuery("[data-bulk-generate-start]");
        if (buttonEl.length) {
          // Restore original button text based on image count
          const imageCount =
            buttonEl
              .closest(".wrap")
              .find("[data-bulk-generate-progress-bar]")
              .data("max") || 0;
          if (imageCount > 0) {
            const originalText =
              imageCount === 1
                ? __("Generate Alt Text for %d Image", "a11y-alt-text").replace(
                    "%d",
                    imageCount
                  )
                : __(
                    "Generate Alt Text for %d Images",
                    "a11y-alt-text"
                  ).replace("%d", imageCount);
            buttonEl.text(originalText);
          }
        }
      } catch (e) {
        // Ignore localStorage errors
      }
      banner.remove();
    });
  }

  function isPostDirty() {
    try {
      // Check for Gutenberg
      if (window.wp && wp.data && wp.blocks) {
        return wp.data.select("core/editor").isEditedPostDirty();
      }

      // Check for Classic Editor (TinyMCE)
      if (window.tinymce && tinymce.editors) {
        for (let editorId in tinymce.editors) {
          const editor = tinymce.editors[editorId];
          if (editor && editor.isDirty && editor.isDirty()) {
            return true;
          }
        }
      }

      // Check for any forms with unsaved changes
      const forms = document.querySelectorAll("form");
      for (let form of forms) {
        if (form.classList.contains("dirty") || form.dataset.dirty === "true") {
          return true;
        }
      }
    } catch (error) {
      console.error("Error checking if post is dirty:", error);
      return true;
    }

    // Assume clean if no editor detected
    return false;
  }

  function editHistoryAJAX(attachmentId, altText = "") {
    if (!attachmentId) {
      const error = new Error(__("Attachment ID is missing", "a11y-alt-text"));
      console.error("editHistoryAJAX error:", error);
      return Promise.reject(error);
    }

    return new Promise((resolve, reject) => {
      jQuery.ajax({
        type: "post",
        dataType: "json",
        data: {
          action: "a11y_edit_history",
          security: wp_a11y.security_edit_history,
          attachment_id: attachmentId,
          alt_text: altText,
        },
        url: wp_a11y.ajax_url,
        success: function (response) {
          resolve(response);
        },
        error: function (response) {
          const error = new Error("AJAX request failed");
          console.error("editHistoryAJAX failed:", error);
          reject(error);
        },
      });
    });
  }

  function singleGenerateAJAX(attachmentId, keywords = []) {
    if (!attachmentId) {
      const error = new Error(__("Attachment ID is missing", "a11y-alt-text"));
      console.error("singleGenerateAJAX error:", error);
      return Promise.reject(error);
    }

    return new Promise((resolve, reject) => {
      jQuery.ajax({
        type: "post",
        dataType: "json",
        data: {
          action: "a11y_single_generate",
          security: wp_a11y.security_single_generate,
          attachment_id: attachmentId,
          keywords: keywords,
        },
        url: wp_a11y.ajax_url,
        success: function (response) {
          resolve(response);
        },
        error: function (response) {
          const error = new Error("AJAX request failed");
          console.error("singleGenerateAJAX failed:", error);
          reject(error);
        },
      });
    });
  }

  function bulkGenerateAJAX() {
    if (window.a11y.isProcessing) {
      return;
    }
    window.a11y.setProcessingState(true);

    // Hide Start Over button for entire bulk operation
    jQuery("#a11y-static-start-over-button").hide();

    jQuery.ajax({
      type: "post",
      dataType: "json",
      data: {
        action: "a11y_bulk_generate",
        security: wp_a11y.security_bulk_generate,
        posts_per_page: window.a11y.postsPerPage,
        last_post_id: window.a11y.lastPostId,
        keywords: window.a11y.bulkGenerateKeywords,
        negativeKeywords: window.a11y.bulkGenerateNegativeKeywords,
        mode: window.a11y.bulkGenerateMode,
        onlyAttached: window.a11y.bulkGenerateOnlyAttached,
        onlyNew: window.a11y.bulkGenerateOnlyNew,
        wcProducts: window.a11y.bulkGenerateWCProducts,
        wcOnlyFeatured: window.a11y.bulkGenerateWCOnlyFeatured,
        batchId: window.a11y.bulkGenerateBatchId,
      },
      url: wp_a11y.ajax_url,
      success: function (response) {
        try {
          // Check for URL access error - stop and show clear error message
          if (response.action_required === "url_access_fix") {
            showUrlAccessErrorNotification(response.message);
            return;
          }

          // Reset retry count on successful response (after server comes back up)
          window.a11y.retryCount = 0;

          // Validate state before processing
          window.a11y.validateProgressState();

          // Update progress heading if it was showing retry message
          if (window.a11y.progressHeading.length) {
            const currentHeading = window.a11y.progressHeading.text();
            if (
              currentHeading.includes("Retrying") ||
              currentHeading.includes("Server error")
            ) {
              window.a11y.progressHeading.text(
                __("Processing images...", "a11y-alt-text")
              );
            }
          }

          // Ensure progress values are initialized before adding
          window.a11y.progressCurrent =
            (window.a11y.progressCurrent || 0) + (response.process_count || 0);
          window.a11y.progressSuccessful =
            (window.a11y.progressSuccessful || 0) +
            (response.success_count || 0);

          // Handle skipped images count if present
          if (typeof response.skipped_count !== "undefined") {
            window.a11y.progressSkipped =
              (window.a11y.progressSkipped || 0) + response.skipped_count;
            if (window.a11y.progressSkippedEl) {
              window.a11y.progressSkippedEl.text(window.a11y.progressSkipped);
            }
          }

          window.a11y.lastPostId = response.last_post_id;

          if (window.a11y.progressBarEl.length) {
            window.a11y.progressBarEl.data(
              "current",
              window.a11y.progressCurrent
            );
          }
          if (window.a11y.progressLastPostId.length) {
            window.a11y.progressLastPostId.text(window.a11y.lastPostId);
          }

          // Save progress to localStorage with all processing settings
          try {
            const progress = {
              lastPostId: window.a11y.lastPostId,
              timestamp: Date.now(),
              // Save all processing settings to ensure continuation uses same parameters
              mode: window.a11y.bulkGenerateMode,
              batchId: window.a11y.bulkGenerateBatchId,
              onlyAttached: window.a11y.bulkGenerateOnlyAttached,
              onlyNew: window.a11y.bulkGenerateOnlyNew,
              wcProducts: window.a11y.bulkGenerateWCProducts,
              wcOnlyFeatured: window.a11y.bulkGenerateWCOnlyFeatured,
              keywords: window.a11y.bulkGenerateKeywords,
              negativeKeywords: window.a11y.bulkGenerateNegativeKeywords,
              // Save complete progress bar state
              progressCurrent: window.a11y.progressCurrent,
              progressSuccessful: window.a11y.progressSuccessful,
              progressMax: window.a11y.progressMax,
              progressSkipped: window.a11y.progressSkipped || 0,
            };
            localStorage.setItem(
              "a11y_bulk_progress",
              JSON.stringify(progress)
            );
          } catch (e) {
            // Ignore localStorage errors
          }
          if (window.a11y.progressCurrentEl.length) {
            window.a11y.progressCurrentEl.text(window.a11y.progressCurrent);
          }
          if (window.a11y.progressSuccessfulEl.length) {
            window.a11y.progressSuccessfulEl.text(
              window.a11y.progressSuccessful
            );
          }

          const percentage = window.a11y.safePercentage(
            window.a11y.progressCurrent,
            window.a11y.progressMax
          );
          if (window.a11y.progressBarEl.length) {
            window.a11y.progressBarEl.css("width", percentage + "%");
          }
          if (window.a11y.progressPercent.length) {
            window.a11y.progressPercent.text(Math.round(percentage) + "%");
          }

          if (response.recursive) {
            // Reset retry count on successful batch
            window.a11y.retryCount = 0;
            // Reset flag before recursive call to allow next batch
            window.a11y.setProcessingState(false);
            setTimeout(() => {
              bulkGenerateAJAX();
            }, 100);
          } else {
            // Reset retry count on completion
            window.a11y.retryCount = 0;
            window.a11y.setProcessingState(false);

            // Show Start Over button only if there's still a session to clear
            if (localStorage.getItem("a11y_bulk_progress")) {
              jQuery("#a11y-static-start-over-button").show();
            }

            if (window.a11y.progressButtonCancel.length) {
              window.a11y.progressButtonCancel.hide();
            }
            if (window.a11y.progressBarWrapper.length) {
              window.a11y.progressBarWrapper.hide();
            }
            if (window.a11y.progressButtonFinished.length) {
              window.a11y.progressButtonFinished.show();
            }
            if (window.a11y.progressHeading.length) {
              window.a11y.progressHeading.text(
                response.message || __("Update complete!", "a11y-alt-text")
              );
            }

            // Clean up processing animations when complete
            jQuery("[data-bulk-generate-progress-bar]").removeClass(
              "a11y-progress-pulse"
            );
            // Show subtitle with skip reasons if available
            const progressSubtitle = jQuery(
              "[data-bulk-generate-progress-subtitle]"
            );
            if (progressSubtitle.length) {
              let subtitleText =
                response.subtitle && response.subtitle.trim()
                  ? response.subtitle
                  : "";

              // Add URL access errors to skip reasons if any occurred
              if (window.a11y.urlAccessErrorCount > 0) {
                const urlErrorText =
                  window.a11y.urlAccessErrorCount === 1
                    ? "1 URL access failure"
                    : `${window.a11y.urlAccessErrorCount} URL access failures`;

                const settingsUrl = `${wp_a11y.settings_page_url}#a11y_error_logs_container`;
                const urlErrorWithLink = `${urlErrorText} (<a href="${settingsUrl}" target="_blank" style="color: inherit; text-decoration: underline;">see error logs for details</a>)`;

                if (subtitleText) {
                  subtitleText += `, ${urlErrorWithLink}`;
                } else {
                  subtitleText = `Skip reasons: ${urlErrorWithLink}`;
                }
              }

              if (subtitleText) {
                progressSubtitle
                  .attr("data-skipped", "")
                  .find("span")
                  .html(subtitleText);
                progressSubtitle.show();
              } else {
                progressSubtitle.hide();
              }
            }
            window.a11y.redirectUrl = response?.redirect_url;

            // Clear progress from localStorage when complete
            try {
              localStorage.removeItem("a11y_bulk_progress");
            } catch (e) {
              // Ignore localStorage errors
            }
          }
        } catch (error) {
          console.error("bulkGenerateAJAX error:", error);
          handleBulkGenerationError(error);
        }
      },
      error: function (response) {
        try {
          const error = new Error("AJAX request failed during bulk generation");
          console.error("bulkGenerateAJAX AJAX failed:", error.message);
          handleBulkGenerationError(error, response);
        } catch (e) {
          // Fallback if console.error fails
          const error = new Error("AJAX request failed during bulk generation");
          handleBulkGenerationError(error, response);
        }
      },
    });
  }

  function handleBulkGenerationError(error, response) {
    // Check if this is a retryable server error - be more aggressive about retrying
    const isServerError =
      response &&
      (response.status >= 500 ||
        response.status === 0 ||
        response.status === 408 ||
        response.status === 405 ||
        response.status === 502 ||
        response.status === 503 ||
        response.status === 504);
    const hasTimeoutError =
      error.message.includes("timeout") ||
      error.message.includes("network") ||
      error.message.includes("failed");
    const isAjaxFailure = error.message.includes("AJAX request failed");
    const isRetryable = isServerError || hasTimeoutError || isAjaxFailure;

    const errorDetails = {
      errorMessage: error.message,
      errorType: error.name || "Unknown",
      responseStatus: response?.status,
      responseStatusText: response?.statusText,
      responseText: response?.responseText?.substring(0, 500),
      ajaxSettings: {
        url: response?.responseURL || "unknown",
        method: "POST",
        timeout: response?.timeout || "default",
      },
      imagesProcessed: window.a11y.progressCurrent || 0,
      batchSize: window.a11y.postsPerPage || 5,
      memoryUsage: performance.memory
        ? Math.round(performance.memory.usedJSHeapSize / 1048576) + "MB"
        : "unknown",
      errorClassification: {
        isServerError,
        hasTimeoutError,
        isAjaxFailure,
        isRetryable,
      },
      retryCount: window.a11y.retryCount,
      maxRetries: window.a11y.maxRetries,
      timestamp: Date.now(),
    };

    console.error("Bulk generation error details:", errorDetails);

    // Store error for debugging - keep last 5 errors
    if (!window.a11y.errorHistory) {
      window.a11y.errorHistory = [];
    }
    window.a11y.errorHistory.push(errorDetails);
    if (window.a11y.errorHistory.length > 5) {
      window.a11y.errorHistory.shift();
    }

    // Save to localStorage for persistence across page reloads
    try {
      localStorage.setItem(
        "a11y_error_history",
        JSON.stringify(window.a11y.errorHistory)
      );
    } catch (e) {
      // If localStorage fails, limit in-memory storage to prevent memory leaks
      if (window.a11y.errorHistory.length > 10) {
        window.a11y.errorHistory = window.a11y.errorHistory.slice(-3); // Keep only last 3
      }
    }

    if (isRetryable && window.a11y.retryCount < window.a11y.maxRetries) {
      window.a11y.retryCount++;

      console.error(
        `Retrying bulk generation (attempt ${window.a11y.retryCount}/${window.a11y.maxRetries})`
      );

      // Update UI to show retry status
      if (window.a11y.progressHeading.length) {
        const retryText = __(
          "Server error - retrying in 2 seconds...",
          "a11y-alt-text"
        );
        window.a11y.progressHeading.text(retryText);
      }

      // Retry after simple 2-second delay
      setTimeout(() => {
        console.error("Executing retry attempt", window.a11y.retryCount);
        if (window.a11y.progressHeading.length) {
          const retryingText = __(
            "Retrying bulk generation...",
            "a11y-alt-text"
          );
          window.a11y.progressHeading.text(retryingText);
        }
        // Reset processing flag before retry to allow the new request
        window.a11y.setProcessingState(false);
        bulkGenerateAJAX();
      }, 2000);
    } else {
      // Max retries reached or non-retryable error - stop processing
      window.a11y.setProcessingState(false);
      window.a11y.retryCount = 0; // Reset for next bulk operation

      // Show Start Over button if there's a session to clear
      if (localStorage.getItem("a11y_bulk_progress")) {
        jQuery("#a11y-static-start-over-button").show();
      }

      // Clean up memory
      window.a11y.cleanup();

      if (window.a11y.progressButtonCancel.length) {
        window.a11y.progressButtonCancel.hide();
      }
      if (window.a11y.progressBarWrapper.length) {
        window.a11y.progressBarWrapper.hide();
      }
      if (window.a11y.progressButtonFinished.length) {
        window.a11y.progressButtonFinished.show();
      }
      if (window.a11y.progressHeading.length) {
        const message =
          window.a11y.retryCount >= window.a11y.maxRetries
            ? __(
                "Update stopped after multiple server errors. Your progress has been saved - you can restart to continue.",
                "a11y-alt-text"
              )
            : __(
                "Update stopped due to an error. Your progress has been saved - you can restart to continue.",
                "a11y-alt-text"
              );
        window.a11y.progressHeading.text(message);
      }

      alert(
        __(
          "Bulk generation encountered an error. Your progress has been saved.",
          "a11y-alt-text"
        )
      );
    }
  }

  function enrichPostContentAJAX(
    postId,
    overwrite = false,
    processExternal = false,
    keywords = []
  ) {
    if (!postId) {
      const error = new Error(__("Post ID is missing", "a11y-alt-text"));
      console.error("enrichPostContentAJAX error:", error);
      return Promise.reject(error);
    }

    return new Promise((resolve, reject) => {
      jQuery.ajax({
        type: "post",
        dataType: "json",
        data: {
          action: "a11y_enrich_post_content",
          security: wp_a11y.security_enrich_post_content,
          post_id: postId,
          overwrite: overwrite,
          process_external: processExternal,
          keywords: keywords,
        },
        url: wp_a11y.ajax_url,
        success: function (response) {
          resolve(response);
        },
        error: function (response) {
          const error = new Error("AJAX request failed");
          console.error("enrichPostContentAJAX failed:", error);
          reject(error);
        },
      });
    });
  }

  function extractKeywords(content) {
    return content
      .split(",")
      .map(function (item) {
        return item.trim();
      })
      .filter(function (item) {
        return item.length > 0;
      })
      .slice(0, 6);
  }

  jQuery("[data-edit-history-trigger]").on("click", async function () {
    const triggerEl = this;
    const attachmentId = triggerEl.dataset.attachmentId;
    const inputEl = document.getElementById(
      "edit-history-input-" + attachmentId
    );
    const altText = inputEl.value.replace(/\n/g, "");

    triggerEl.disabled = true;

    try {
      const response = await editHistoryAJAX(attachmentId, altText);
      if (response.status !== "success") {
        alert(__("Unable to update alt text for this image.", "a11y-alt-text"));
      }

      const successEl = document.getElementById(
        "edit-history-success-" + attachmentId
      );
      successEl.classList.remove("hidden");
      setTimeout(() => {
        successEl.classList.add("hidden");
      }, 2000);
    } catch (error) {
      alert(
        __("An error occurred while updating the alt text.", "a11y-alt-text")
      );
    } finally {
      triggerEl.disabled = false;
    }
  });

  // Handle static Start Over button click
  jQuery("#a11y-static-start-over-button").on("click", function () {
    try {
      localStorage.removeItem("a11y_bulk_progress");
      localStorage.removeItem("a11y_error_history");

      // Complete memory cleanup
      window.a11y.cleanup();

      // Reset all window.a11y state
      window.a11y.lastPostId = 0;
      window.a11y.hasRecoveredSession = false;
      window.a11y.isContinuation = false;
      window.a11y.progressCurrent = 0;
      window.a11y.progressSuccessful = 0;
      window.a11y.progressSkipped = 0;
      window.a11y.progressMax = 0;
      window.a11y.recoveryBannerShown = false;

      // Remove any recovery banner
      jQuery(".a11y-recovery-banner").remove();

      // Update the UI to normal state
      location.reload();
    } catch (error) {
      console.error("Error in Start Over button handler:", error);
      // Even if there's an error, reload to reset the state
      location.reload();
    }
  });

  jQuery("[data-bulk-generate-start]").on("click", function () {
    const action = getQueryParam("a11y_action") || "normal";
    const batchId = getQueryParam("a11y_batch_id") || 0;

    if (action === "bulk-select-generate" && !batchId) {
      alert(__("Invalid batch ID", "a11y-alt-text"));
    }

    window.a11y["bulkGenerateKeywords"] = extractKeywords(
      jQuery("[data-bulk-generate-keywords]").val() ?? ""
    );
    window.a11y["bulkGenerateNegativeKeywords"] = extractKeywords(
      jQuery("[data-bulk-generate-negative-keywords]").val() ?? ""
    );
    window.a11y["progressWrapperEl"] = jQuery(
      "[data-bulk-generate-progress-wrapper]"
    );
    window.a11y["progressHeading"] = jQuery(
      "[data-bulk-generate-progress-heading]"
    );
    window.a11y["progressBarWrapper"] = jQuery(
      "[data-bulk-generate-progress-bar-wrapper]"
    );
    window.a11y["progressBarEl"] = jQuery("[data-bulk-generate-progress-bar]");
    window.a11y["progressPercent"] = jQuery(
      "[data-bulk-generate-progress-percent]"
    );
    window.a11y["progressLastPostId"] = jQuery(
      "[data-bulk-generate-last-post-id]"
    );
    window.a11y["progressCurrentEl"] = jQuery(
      "[data-bulk-generate-progress-current]"
    );
    // Only initialize from HTML if not already set by recovery
    if (typeof window.a11y["progressCurrent"] === "undefined") {
      window.a11y["progressCurrent"] = window.a11y.progressBarEl.length
        ? window.a11y.progressBarEl.data("current")
        : 0;
    }
    window.a11y["progressSuccessfulEl"] = jQuery(
      "[data-bulk-generate-progress-successful]"
    );
    if (typeof window.a11y["progressSuccessful"] === "undefined") {
      window.a11y["progressSuccessful"] = window.a11y.progressBarEl.length
        ? window.a11y.progressBarEl.data("successful")
        : 0;
    }
    window.a11y["progressSkippedEl"] = jQuery(
      "[data-bulk-generate-progress-skipped]"
    );
    if (typeof window.a11y["progressSkipped"] === "undefined") {
      window.a11y["progressSkipped"] = 0;
    }
    // Set progressMax from DOM if not already set by recovery session
    if (!window.a11y.hasRecoveredSession || window.a11y["progressMax"] === 0) {
      window.a11y["progressMax"] = window.a11y.progressBarEl.length
        ? window.a11y.progressBarEl.data("max")
        : 100;
    }
    window.a11y["progressButtonCancel"] = jQuery("[data-bulk-generate-cancel]");
    window.a11y["progressButtonFinished"] = jQuery(
      "[data-bulk-generate-finished]"
    );

    if (action === "bulk-select-generate") {
      window.a11y["bulkGenerateMode"] = "bulk-select";
      window.a11y["bulkGenerateBatchId"] = batchId;
    } else {
      window.a11y["bulkGenerateMode"] = jQuery(
        "[data-bulk-generate-mode-all]"
      ).is(":checked")
        ? "all"
        : "missing";
      window.a11y["bulkGenerateOnlyAttached"] = jQuery(
        "[data-bulk-generate-only-attached]"
      ).is(":checked")
        ? "1"
        : "0";
      window.a11y["bulkGenerateOnlyNew"] = jQuery(
        "[data-bulk-generate-only-new]"
      ).is(":checked")
        ? "1"
        : "0";
      window.a11y["bulkGenerateWCProducts"] = jQuery(
        "[data-bulk-generate-wc-products]"
      ).is(":checked")
        ? "1"
        : "0";
      window.a11y["bulkGenerateWCOnlyFeatured"] = jQuery(
        "[data-bulk-generate-wc-only-featured]"
      ).is(":checked")
        ? "1"
        : "0";
    }

    jQuery("#bulk-generate-form").hide();
    // Explicitly hide the recovery buttons when form is hidden using CSS class
    window.a11y.hideButtons();
    if (window.a11y.progressWrapperEl.length) {
      window.a11y.progressWrapperEl.show();

      // Add processing animations to show the page is alive
      const progressHeading = jQuery("[data-bulk-generate-progress-heading]");
      if (progressHeading.length) {
        progressHeading.html(
          __("Processing Images", "a11y-alt-text") +
            '<span class="a11y-processing-dots"></span>'
        );
      }

      // Add pulse animation to the progress bar
      const progressBar = jQuery("[data-bulk-generate-progress-bar]");
      if (progressBar.length) {
        progressBar.addClass("a11y-progress-pulse");
      }
    }

    // If continuing from localStorage, restore the exact progress state
    if (window.a11y.isContinuation) {
      const lastId = window.a11y.lastPostId || 0;

      // Restore the exact progress bar state from localStorage
      if (window.a11y.progressBarEl.length) {
        window.a11y.progressBarEl.data("current", window.a11y.progressCurrent);
        window.a11y.progressBarEl.data(
          "successful",
          window.a11y.progressSuccessful
        );
        window.a11y.progressBarEl.data("max", window.a11y.progressMax);

        // Update progress display elements to show current state
        if (window.a11y.progressCurrentEl.length) {
          window.a11y.progressCurrentEl.text(window.a11y.progressCurrent);
        }
        if (window.a11y.progressSuccessfulEl.length) {
          window.a11y.progressSuccessfulEl.text(window.a11y.progressSuccessful);
        }
        if (window.a11y.progressSkippedEl.length) {
          window.a11y.progressSkippedEl.text(window.a11y.progressSkipped || 0);
        }

        // Update progress bar visual
        const percentage = window.a11y.safePercentage(
          window.a11y.progressCurrent,
          window.a11y.progressMax
        );
        window.a11y.progressBarEl.css("width", percentage + "%");
        if (window.a11y.progressPercent.length) {
          window.a11y.progressPercent.text(Math.round(percentage) + "%");
        }
      }

      // Add a clean continuation banner above the form (inside max-w-6xl wrapper)
      const continuationBanner = jQuery(
        '<div class="notice notice-success" style="margin: 15px 0; padding: 10px 15px; border-left: 4px solid #00a32a;"><p style="margin: 0; font-weight: 500;"><span class="dashicons dashicons-update" style="margin-right: 5px;"></span>' +
          __(
            "Resuming from where you left off - starting after image ID %d",
            "a11y-alt-text"
          ).replace("%d", lastId) +
          "</p></div>"
      );

      jQuery(".wrap.max-w-6xl")
        .find("#bulk-generate-form")
        .before(continuationBanner);

      // Update progress heading when processing starts
      if (window.a11y.progressHeading.length) {
        window.a11y.progressHeading.text(
          __(
            "Continuing bulk generation from image ID %d...",
            "a11y-alt-text"
          ).replace("%d", lastId)
        );
      }
    }

    bulkGenerateAJAX();
  });

  jQuery("[data-bulk-generate-mode-all]").on("change", function () {
    window.location.href = this.dataset.url;
  });

  jQuery("[data-bulk-generate-only-attached]").on("change", function () {
    window.location.href = this.dataset.url;
  });

  jQuery("[data-bulk-generate-only-new]").on("change", function () {
    window.location.href = this.dataset.url;
  });

  jQuery("[data-bulk-generate-wc-products]").on("change", function () {
    window.location.href = this.dataset.url;
  });

  jQuery("[data-bulk-generate-wc-only-featured]").on("change", function () {
    window.location.href = this.dataset.url;
  });

  // Handle permanent Start Over button click
  jQuery(document).on("click", "#a11y-start-over-button", function () {
    try {
      // Clear all localStorage progress data
      localStorage.removeItem("a11y_bulk_progress");
      localStorage.removeItem("a11y_error_history");

      // Complete memory cleanup
      window.a11y.cleanup();

      // Reset all window.a11y state
      window.a11y.lastPostId = 0;
      window.a11y.hasRecoveredSession = false;
      window.a11y.isContinuation = false;
      window.a11y.remainingImages = null;
      window.a11y.progressCurrent = 0;
      window.a11y.progressSuccessful = 0;
      window.a11y.progressSkipped = 0;
      window.a11y.retryCount = 0;

      // Update button visibility after clearing session
      window.a11y.updateStartOverButtonVisibility();

      // Reload page to reset UI
      window.location.reload();
    } catch (e) {
      // Still reload page even if localStorage operations fail
      window.location.reload();
    }
  });

  jQuery("[data-post-bulk-generate]").on("click", async function (event) {
    if (this.getAttribute("href") !== "#a11y-bulk-generate") {
      return;
    }

    event.preventDefault();

    if (isPostDirty()) {
      // Ask for consent
      const consent = confirm(
        __(
          "[A11Y] Make sure to save any changes before proceeding -- any unsaved changes will be lost. Are you sure you want to continue?",
          "a11y-alt-text"
        )
      );

      // If user doesn't consent, return
      if (!consent) {
        return;
      }
    }

    const postId = document.getElementById("post_ID")?.value;
    const buttonLabel = this.querySelector("span");
    const updateNotice = this.nextElementSibling;
    const buttonLabelText = buttonLabel.innerText;
    const overwrite =
      document.querySelector("[data-post-bulk-generate-overwrite]")?.checked ||
      false;
    const processExternal =
      document.querySelector("[data-post-bulk-generate-process-external]")
        ?.checked || false;
    const keywordsCheckbox = document.querySelector(
      "[data-post-bulk-generate-keywords-checkbox]"
    );
    const keywordsTextField = document.querySelector(
      "[data-post-bulk-generate-keywords]"
    );
    const keywords = [];

    if (!postId) {
      updateNotice.innerText = __("This is not a valid post.", "a11y-alt-text");
      updateNotice.classList.add("a11y-update-notice--error");
      return;
    }

    try {
      this.classList.add("disabled");
      buttonLabel.innerText = __("Processing...", "a11y-alt-text");

      // Generate alt text for all images in the post
      const response = await enrichPostContentAJAX(
        postId,
        overwrite,
        processExternal,
        keywords
      );

      if (response.success) {
        window.location.reload();
      } else {
        throw new Error(
          __(
            "Unable to generate alt text. Check error logs for details.",
            "a11y-alt-text"
          )
        );
      }
    } catch (error) {
      updateNotice.innerText =
        error.message || __("An error occurred.", "a11y-alt-text");
      updateNotice.classList.add("a11y-update-notice--error");
    } finally {
      this.classList.remove("disabled");
      buttonLabel.innerText = buttonLabelText;
    }
  });

  document.addEventListener("DOMContentLoaded", () => {
    // If not using Gutenberg, return
    if (!wp?.blocks) {
      return;
    }

    // Fetch the transient message via AJAX
    jQuery.ajax({
      url: wp_a11y.ajax_url,
      type: "GET",
      data: {
        action: "a11y_check_enrich_post_content_transient",
        security: wp_a11y.security_enrich_post_content_transient,
      },
      success: function (response) {
        if (!response?.success) {
          return;
        }

        wp.data
          .dispatch("core/notices")
          .createNotice("success", response.data.message, {
            isDismissible: true,
          });
      },
    });
  });

  /**
   * Empty API key input when clicked "Clear API Key" button
   */
  jQuery('[name="handle_api_key"]').on("click", function () {
    if (this.value === "Clear API Key") {
      jQuery('[name="a11y_api_key"]').val("");
    }
  });

  jQuery(".notice--a11y.is-dismissible").on(
    "click",
    ".notice-dismiss",
    function () {
      jQuery.ajax(wp_a11y.ajax_url, {
        type: "POST",
        data: {
          action: "a11y_expire_insufficient_credits_notice",
          security: wp_a11y.security_insufficient_credits_notice,
        },
      });
    }
  );

  function getQueryParam(name) {
    name = name.replace(/[[]/, "\\[").replace(/[\]]/, "\\]");
    let regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
    let paramSearch = regex.exec(window.location.search);

    return paramSearch === null
      ? ""
      : decodeURIComponent(paramSearch[1].replace(/\+/g, " "));
  }

  function addGenerateButtonToModal(
    replacementId,
    generateButtonId,
    attachmentId
  ) {
    let replacementNode = document.getElementById(replacementId);

    if (!replacementNode) {
      return false;
    }

    // Remove existing button, if any
    let oldGenerateButton = document.getElementById(
      generateButtonId + "-" + attachmentId
    );

    if (oldGenerateButton) {
      oldGenerateButton.remove();
    }

    if (!window.location.href.includes("upload.php")) {
      return false;
    }

    let generateButton = createGenerateButton(
      generateButtonId,
      attachmentId,
      "modal"
    );
    let parentNode = replacementNode.parentNode;
    if (parentNode) {
      parentNode.replaceChild(generateButton, replacementNode);
    }

    return true;
  }

  function createGenerateButton(generateButtonId, attachmentId, context) {
    const generateUrl = new URL(window.location.href);
    generateUrl.searchParams.set("a11y_action", "generate");
    generateUrl.searchParams.set("_wpnonce", wp_a11y.security_url_generate);

    // Button wrapper
    const buttonId = generateButtonId + "-" + attachmentId;
    const button = document.createElement("div");
    button.setAttribute("id", buttonId);

    button.classList.add("description");
    button.classList.add("a11y-generate-button");

    // Clickable anchor inside the wrapper for initiating the action
    const anchor = document.createElement("a");
    anchor.setAttribute("id", buttonId + "-anchor");
    anchor.setAttribute("href", generateUrl);
    anchor.className =
      "button-secondary button-large a11y-generate-button__anchor";

    // Check if the attachment is eligible for generation
    const isAttachmentEligible = (attachmentId) => {
      jQuery.ajax({
        type: "post",
        dataType: "json",
        data: {
          action: "a11y_check_image_eligibility",
          security: wp_a11y.security_check_attachment_eligibility,
          attachment_id: attachmentId,
        },
        url: wp_a11y.ajax_url,
        success: function (response) {
          if (response.status !== "success") {
            const tempAnchor = document.querySelector(`#${buttonId}-anchor`);

            if (tempAnchor) {
              tempAnchor.classList.add("disabled");
            } else {
              anchor.classList.add("disabled");
            }
          }
        },
      });
    };

    // If attachment is eligible, we enable the button
    if (wp_a11y.can_user_upload_files) {
      isAttachmentEligible(attachmentId);
    } else {
      anchor.classList.add("disabled");
    }

    anchor.title = __(
      "A11Y: Update alt text for this single image",
      "a11y-alt-text"
    );
    anchor.onclick = function () {
      this.classList.add("disabled");
      let span = this.querySelector("span");

      if (span) {
        // Create animated dots for processing state
        span.innerHTML =
          __("Processing", "a11y-alt-text") +
          '<span class="a11y-processing-dots"></span>';

        // Add processing state class for better visibility
        this.classList.add("a11y-processing");
      }
    };

    // Button icon
    const img = document.createElement("img");
    img.src = wp_a11y.icon_button_generate;
    img.alt = __("Generate Alt Text with A11Y", "a11y-alt-text");
    anchor.appendChild(img);

    // Button label/text
    const span = document.createElement("span");
    span.innerText = __("Generate Alt Text", "a11y-alt-text");
    anchor.appendChild(span);

    // Append anchor to the button
    button.appendChild(anchor);

    // Notice element below the button,
    // to display "Updated" message when action is successful
    const updateNotice = document.createElement("span");
    updateNotice.classList.add("a11y-update-notice");
    button.appendChild(updateNotice);

    // Event listener to initiate generation
    anchor.addEventListener("click", async function (event) {
      event.preventDefault();

      // If API key is not set, redirect to settings page
      if (!wp_a11y.has_api_key) {
        window.location.href = wp_a11y.settings_page_url + "&api_key_missing=1";
      }

      const titleEl =
        context == "single"
          ? document.getElementById("title")
          : document.querySelector('[data-setting="title"] input');
      const captionEl =
        context == "single"
          ? document.getElementById("attachment_caption")
          : document.querySelector('[data-setting="caption"] textarea');
      const descriptionEl =
        context == "single"
          ? document.getElementById("attachment_content")
          : document.querySelector('[data-setting="description"] textarea');
      const altTextEl =
        context == "single"
          ? document.getElementById("attachment_alt")
          : document.querySelector('[data-setting="alt"] textarea');
      const keywords = [];

      // Hide notice
      if (updateNotice) {
        updateNotice.innerText = "";
        updateNotice.classList.remove(
          "a11y-update-notice--success",
          "a11y-update-notice--error"
        );
      }

      // Generate alt text
      const response = await singleGenerateAJAX(attachmentId, keywords);

      // Update alt text in DOM
      if (response.status === "success") {
        altTextEl.value = response.alt_text;
        altTextEl.dispatchEvent(new Event("change", { bubbles: true }));

        if (wp_a11y.should_update_title === "yes") {
          titleEl.value = response.alt_text;
          titleEl.dispatchEvent(new Event("change", { bubbles: true }));

          if (context == "single") {
            // Add class to label to hide it; initially it behaves as placeholder
            titleEl.previousElementSibling.classList.add("screen-reader-text");
          }
        }

        if (wp_a11y.should_update_caption === "yes") {
          captionEl.value = response.alt_text;
          captionEl.dispatchEvent(new Event("change", { bubbles: true }));
        }

        if (wp_a11y.should_update_description === "yes") {
          descriptionEl.value = response.alt_text;
          descriptionEl.dispatchEvent(new Event("change", { bubbles: true }));
        }

        // A11Y Description + 이미지 유형 실시간 반영
        updateA11yFields(button, response);

        updateNotice.innerText = __("Updated", "a11y-alt-text");
        updateNotice.classList.add("a11y-update-notice--success");

        setTimeout(() => {
          updateNotice.classList.remove("a11y-update-notice--success");
        }, 3000);
      } else {
        let errorMessage = __(
          "Unable to generate alt text. Check error logs for details.",
          "a11y-alt-text"
        );

        if (response?.message) {
          errorMessage = response.message;
        }

        updateNotice.innerText = errorMessage;
        updateNotice.classList.add("a11y-update-notice--error");
      }

      // Reset button
      anchor.classList.remove("disabled", "a11y-processing");
      anchor.querySelector("span").innerHTML = __(
        "Generate Alt Text",
        "a11y-alt-text"
      );
    });

    return button;
  }

  /**
   * AJAX 성공 후 A11Y Description과 이미지 유형 뱃지를 실시간 반영.
   * - post.php (single): DOM에 실제 필드가 있으므로 querySelector로 찾아서 값 반영
   * - upload.php (modal): attachment_fields_to_edit 필드가 Backbone 뷰 밖이라
   *   DOM에 없으므로, 버튼 wrapper 안에 직접 생성/업데이트
   *
   * @param {HTMLElement} buttonEl - .a11y-generate-button div
   * @param {Object}      response - AJAX 응답 { description, img_type }
   */
  function updateA11yFields(buttonEl, response) {
    // ── 1. A11Y Description ──────────────────────────────────────────────
    if (response.description != null) {
      // post.php 상세 편집 페이지: PHP가 렌더링한 textarea가 DOM에 있음
      const descEl =
        document.querySelector('textarea[name*="[a11y_description]"]') ||
        document.querySelector('[data-setting="a11y_description"] textarea');

      if (descEl) {
        // post.php: 기존 textarea에 값 반영
        descEl.value = response.description;
        descEl.dispatchEvent(new Event("change", { bubbles: true }));
      } else {
        // upload.php 모달: DOM에 없으므로 버튼 아래 프리뷰 박스 생성/업데이트
        let previewBox = buttonEl.querySelector(".a11y-desc-preview");
        if (!previewBox) {
          previewBox = document.createElement("div");
          previewBox.className = "a11y-desc-preview";
          previewBox.style.cssText =
            "margin-top:8px;padding:8px 10px;" +
            "background:#f0eeff;border:1px solid #c4b8f5;" +
            "border-radius:6px;font-size:11px;color:#374151;line-height:1.5;";

          const previewLabel = document.createElement("span");
          previewLabel.style.cssText =
            "font-weight:600;display:block;margin-bottom:4px;" +
            "font-size:11px;color:#534AB7;text-transform:uppercase;letter-spacing:0.03em;";
          previewLabel.textContent = "A11Y Description";

          const previewContent = document.createElement("div");
          previewContent.className = "a11y-desc-preview-content";

          previewBox.appendChild(previewLabel);
          previewBox.appendChild(previewContent);
          buttonEl.appendChild(previewBox);
        }
        previewBox.querySelector(".a11y-desc-preview-content").innerHTML =
          response.description;
      }
    }

    // ── 2. 이미지 유형 뱃지
    if (response.img_type) {
      const badgeClass =
        response.img_type === "복합형"
          ? "a11y-type-complex"
          : "a11y-type-simple";

      // post.php: PHP가 렌더링한 뱃지가 DOM에 있을 수 있음
      let badge = document.querySelector(".a11y-type-badge");

      if (badge) {
        badge.textContent = response.img_type;
        badge.className = "a11y-type-badge " + badgeClass;
      } else {
        // upload.php 모달: 버튼 안에 뱃지 생성/업데이트
        let inlineBadge = buttonEl.querySelector(".a11y-type-badge");
        if (!inlineBadge) {
          inlineBadge = document.createElement("span");
          inlineBadge.style.cssText = "display:inline-block;margin-top:6px;";
          buttonEl.appendChild(inlineBadge);
        }
        inlineBadge.className = "a11y-type-badge " + badgeClass;
        inlineBadge.textContent = response.img_type;
      }
    }
  }

  // Utility function to DRY up button injection logic
  function injectGenerateButton(container, attachmentId, context) {
    try {
      // First check if a button already exists to prevent duplicates
      // Use a more specific selector that includes the ID to be absolutely sure
      const existingButton = container.querySelector(
        "#a11y-generate-button-" + attachmentId + ", .a11y-generate-button"
      );
      if (existingButton) {
        return true; // Button already exists, no need to inject another
      }

      let injected = false;
      let button;

      // 1. Try p#alt-text-description
      const altDescP = container.querySelector("p#alt-text-description");
      if (altDescP && altDescP.parentNode) {
        button = createGenerateButton(
          "a11y-generate-button",
          attachmentId,
          context
        );
        altDescP.parentNode.replaceChild(button, altDescP);
        injected = true;
      }

      // 2. Try after alt text input/textarea
      if (!injected) {
        const altInput = container.querySelector(
          '[data-setting="alt"] input, [data-setting="alt"] textarea'
        );
        if (altInput && altInput.parentNode) {
          button = createGenerateButton(
            "a11y-generate-button",
            attachmentId,
            context
          );
          altInput.parentNode.insertBefore(button, altInput.nextSibling);
          injected = true;
        }
      }

      // 3. Try appending to .attachment-details or .media-attachment-details
      if (!injected) {
        const detailsContainer = container.querySelector(
          ".attachment-details, .media-attachment-details"
        );
        if (detailsContainer) {
          button = createGenerateButton(
            "a11y-generate-button",
            attachmentId,
            context
          );
          detailsContainer.appendChild(button);
          injected = true;
        }
      }

      // 4. As a last resort, append to the root
      if (!injected) {
        button = createGenerateButton(
          "a11y-generate-button",
          attachmentId,
          context
        );
        container.appendChild(button);
        injected = true;
      }

      return injected;
    } catch (error) {
      console.error("[A11Y] Error injecting button:", error);
      return false;
    }
  }

  function insertGenerationButton(hostWrapper, generationButton) {
    // If the wrapping class already has a BUTTON element, replace it with ours.
    // Otherwise insert at end.
    if (!hostWrapper.hasChildNodes()) {
      hostWrapper.appendChild(generationButton);
      return;
    }

    for (const childNode of hostWrapper.childNodes) {
      if (childNode.nodeName == "BUTTON") {
        hostWrapper.replaceChild(generationButton, childNode);
        return;
      }
    }

    // If we get here, there was no textarea elelment, so just append to the end again.
    hostWrapper.appendChild(generationButton);
  }

  /**
   * Manage Generation for Single Image
   */
  document.addEventListener("DOMContentLoaded", async () => {
    const isAttachmentPage =
      window.location.href.includes("post.php") &&
      jQuery("body").hasClass("post-type-attachment");
    const isEditPost =
      window.location.href.includes("post-new.php") ||
      (window.location.href.includes("post.php") &&
        !jQuery("body").hasClass("post-type-attachment"));
    const isAttachmentModal = window.location.href.includes("upload.php");
    let attachmentId = null;
    let generateButtonId = "a11y-generate-button";

    if (isAttachmentPage) {
      // Editing media library image from the list view
      attachmentId = getQueryParam("post");

      // Bail early if no post ID.
      if (!attachmentId) {
        return false;
      }

      attachmentId = parseInt(attachmentId, 10);

      // Bail early if post ID is not a number.
      if (!attachmentId) {
        return;
      }

      let hostWrapper = document.getElementsByClassName(
        "attachment-alt-text"
      )[0];

      if (hostWrapper) {
        let generateButton = createGenerateButton(
          generateButtonId,
          attachmentId,
          "single"
        );
        setTimeout(() => {
          insertGenerationButton(hostWrapper, generateButton);
        }, 200);
      }
    } else if (isAttachmentModal || isEditPost) {
      // Media library grid view modal window
      attachmentId = getQueryParam("item");

      // Initial click to open the media library grid view attachment modal:
      jQuery(document).on("click", "ul.attachments li.attachment", function () {
        let element = jQuery(this);

        // Bail early if no data-id attribute.
        if (!element.attr("data-id")) {
          return;
        }

        attachmentId = parseInt(element.attr("data-id"), 10);

        // Bail early if post ID is not a number.
        if (!attachmentId) {
          return;
        }

        addGenerateButtonToModal(
          "alt-text-description",
          generateButtonId,
          attachmentId
        );
      });

      // Click on the next/previous image arrows from the media library modal window:
      document.addEventListener("click", function (event) {
        attachmentModalChangeHandler(event, "button-click", generateButtonId);
      });

      // Keyboard navigation for the media library modal window:
      document.addEventListener("keydown", function (event) {
        if (event.key === "ArrowRight" || event.key === "ArrowLeft") {
          attachmentModalChangeHandler(event, "keyboard", generateButtonId);
        }
      });

      // Bail early if no post ID.
      if (!attachmentId) {
        return false;
      }
    } else {
      return false;
    }
  });

  /**
   * Make bulk action parent option disabled
   */
  document.addEventListener("DOMContentLoaded", () => {
    jQuery(
      '.tablenav .bulkactions select option[value="alttext_options"]'
    ).attr("disabled", "disabled");
  });

  /**
   * Handle button injection on modal navigation
   *
   * @param {Event} event - The DOM event triggered by user interaction, such as a click or keydown.
   * @param {string} eventType - A string specifying the type of event that initiated the modal navigation.
   * @param {string} generateButtonId - A string containing the button ID that will be injected into the modal.
   */
  function attachmentModalChangeHandler(event, eventType, generateButtonId) {
    // Bail early if not clicking on the modal navigation.
    if (
      eventType === "button-click" &&
      !event.target.matches(".media-modal .right, .media-modal .left")
    ) {
      return;
    }

    // Get attachment ID from URL.
    const urlParams = new URLSearchParams(window.location.search);
    const attachmentId = urlParams.get("item");

    // Bail early if post ID is not a number.
    if (!attachmentId) {
      return;
    }

    addGenerateButtonToModal(
      "alt-text-description",
      generateButtonId,
      attachmentId
    );
  }

  /**
   * Native override to play nice with other plugins that may also be modifying this modal.
   * Adds the generate button to the media modal when the attachment details are rendered.
   *
   */
  const attachGenerateButtonToModal = () => {
    if (wp?.media?.view?.Attachment?.Details?.prototype?.render) {
      const origRender = wp.media.view.Attachment.Details.prototype.render;
      wp.media.view.Attachment.Details.prototype.render = function () {
        const result = origRender.apply(this, arguments);
        const container = this.$el ? this.$el[0] : null;
        if (container) {
          // Clean up any existing observer to prevent memory leaks
          if (this._a11yObserver) {
            this._a11yObserver.disconnect();
            delete this._a11yObserver;
          }

          // Use a more efficient observer with a debounce mechanism
          let debounceTimer = null;
          const tryInject = () => {
            // Clear any pending injection to avoid multiple rapid calls
            if (debounceTimer) {
              clearTimeout(debounceTimer);
            }

            // Debounce the injection to avoid excessive processing
            debounceTimer = setTimeout(() => {
              // Check if button already exists before doing any work
              if (!container.querySelector(".a11y-generate-button")) {
                injectGenerateButton(container, this.model.get("id"), "modal");
              }

              // Disconnect observer after successful injection to prevent further processing
              if (this._a11yObserver) {
                this._a11yObserver.disconnect();
                delete this._a11yObserver;
              }
            }, 50); // Small delay to batch DOM changes
          };

          // Create a new observer with limited scope
          this._a11yObserver = new MutationObserver(tryInject);

          // Only observe specific changes to reduce overhead
          this._a11yObserver.observe(container, {
            childList: true, // Watch for child additions/removals
            subtree: true, // Watch the entire subtree
            attributes: false, // Don't watch attributes (reduces overhead)
            characterData: false, // Don't watch text content (reduces overhead)
          });

          // Try immediate injection but with a slight delay to let other scripts finish
          setTimeout(() => {
            if (!container.querySelector(".a11y-generate-button")) {
              injectGenerateButton(container, this.model.get("id"), "modal");
            }
          }, 10);
        }
        return result;
      };
    }
  };

  attachGenerateButtonToModal();

  document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("form#alttextai-csv-import");
    if (form) {
      const input = form.querySelector('input[type="file"]');
      const languageSelector = document.getElementById(
        "a11y-csv-language-selector"
      );
      const languageSelect = document.getElementById("a11y-csv-language");

      if (input) {
        input.addEventListener("change", async (event) => {
          const files = event.target.files;
          form.dataset.fileLoaded = files?.length > 0 ? "true" : "false";

          // If no file selected or no language selector, skip preview
          if (!files?.length || !languageSelector || !languageSelect) {
            if (languageSelector) {
              languageSelector.classList.add("hidden");
            }
            return;
          }

          const file = files[0];

          // Validate file type
          if (!file.name.toLowerCase().endsWith(".csv")) {
            languageSelector.classList.add("hidden");
            return;
          }

          // Validate wp_a11y is available
          if (
            typeof wp_a11y === "undefined" ||
            !wp_a11y.ajax_url ||
            !wp_a11y.security_preview_csv
          ) {
            console.error("A11Y: Required configuration not loaded");
            languageSelector.classList.add("hidden");
            return;
          }

          // Show loading state
          languageSelect.disabled = true;
          languageSelect.innerHTML =
            '<option value="">' +
            __("Detecting languages...", "a11y-alt-text") +
            "</option>";
          languageSelector.classList.remove("hidden");

          // Create form data for preview
          const formData = new FormData();
          formData.append("action", "a11y_preview_csv");
          formData.append("security", wp_a11y.security_preview_csv);
          formData.append("csv", file);

          try {
            const response = await fetch(wp_a11y.ajax_url, {
              method: "POST",
              body: formData,
            });

            if (!response.ok) {
              throw new Error(`HTTP error: ${response.status}`);
            }

            const data = await response.json();

            if (data.status === "success") {
              populateLanguageSelector(data.languages, data.preferred_lang);
            } else {
              // No languages detected or error - hide selector
              if (!data.languages || Object.keys(data.languages).length === 0) {
                languageSelector.classList.add("hidden");
              }
            }
          } catch (error) {
            console.error("A11Y: Error previewing CSV:", error);
            languageSelector.classList.add("hidden");
          } finally {
            if (!languageSelector.classList.contains("hidden")) {
              languageSelect.disabled = false;
            }
          }
        });
      }

      /**
       * Populate the language selector dropdown with detected languages.
       *
       * @param {Object} languages - Object mapping language codes to display names
       * @param {string} preferredLang - Previously selected language to pre-select
       */
      function populateLanguageSelector(languages, preferredLang) {
        if (!languageSelect) return;

        // Clear existing options and add default
        languageSelect.innerHTML =
          '<option value="">' +
          __("Default (alt_text column)", "a11y-alt-text") +
          "</option>";

        // Check if any languages detected
        if (!languages || Object.keys(languages).length === 0) {
          languageSelector.classList.add("hidden");
          return;
        }

        // Add language options
        for (const [code, name] of Object.entries(languages)) {
          const option = document.createElement("option");
          option.value = code;
          option.textContent = `${name} (alt_text_${code})`;

          // Pre-select if matches user preference
          if (code === preferredLang) {
            option.selected = true;
          }

          languageSelect.appendChild(option);
        }

        // Show selector
        languageSelector.classList.remove("hidden");
      }
    }
  });

  function extendMediaTemplate() {
    const previousAttachmentDetails = wp.media.view.Attachment.Details;
    wp.media.view.Attachment.Details = previousAttachmentDetails.extend({
      A11YAnchorClick: async function (event) {
        event.preventDefault();
        const attachmentId = this.model.id;
        const anchor = event.currentTarget;
        const attachmentDetails = anchor.closest(".attachment-details");
        const generateButton = anchor.closest(".a11y-generate-button");
        const updateNotice = generateButton.querySelector(
          ".a11y-update-notice"
        );

        // Loading state
        anchor.classList.add("disabled");
        const anchorLabel = anchor.querySelector("span");

        if (anchorLabel) {
          // Create animated dots for processing state
          anchorLabel.innerHTML =
            __("Processing", "a11y-alt-text") +
            '<span class="a11y-processing-dots"></span>';

          // Add processing state class for better visibility
          anchor.classList.add("a11y-processing");
        }

        // If API key is not set, redirect to settings page
        if (!wp_a11y.has_api_key) {
          window.location.href =
            wp_a11y.settings_page_url + "&api_key_missing=1";
        }

        const titleEl = attachmentDetails.querySelector(
          '[data-setting="title"] input'
        );
        const captionEl = attachmentDetails.querySelector(
          '[data-setting="caption"] textarea'
        );
        const descriptionEl = attachmentDetails.querySelector(
          '[data-setting="description"] textarea'
        );
        const altTextEl = attachmentDetails.querySelector(
          '[data-setting="alt"] textarea'
        );
        const keywords = [];

        // Hide notice
        if (updateNotice) {
          updateNotice.innerText = "";
          updateNotice.classList.remove(
            "a11y-update-notice--success",
            "a11y-update-notice--error"
          );
        }

        // Generate alt text
        const response = await singleGenerateAJAX(attachmentId, keywords);

        // Update alt text in DOM
        if (response.status === "success") {
          // A11Y Description + 이미지 유형을 먼저 반영
          // altTextEl.dispatchEvent가 Backbone 리렌더를 유발하기 전에 처리해야
          // save-complete 사이클 동안 값이 안전하게 들어감
          updateA11yFields(generateButton, response);

          altTextEl.value = response.alt_text;
          altTextEl.dispatchEvent(new Event("change", { bubbles: true }));

          if (wp_a11y.should_update_title === "yes") {
            titleEl.value = response.alt_text;
            titleEl.dispatchEvent(new Event("change", { bubbles: true }));
          }

          if (wp_a11y.should_update_caption === "yes") {
            captionEl.value = response.alt_text;
            captionEl.dispatchEvent(new Event("change", { bubbles: true }));
          }

          if (wp_a11y.should_update_description === "yes") {
            descriptionEl.value = response.alt_text;
            descriptionEl.dispatchEvent(new Event("change", { bubbles: true }));
          }

          // A11Y Description + 이미지 유형 실시간 반영
          updateA11yFields(generateButton, response);

          updateNotice.innerText = __("Updated", "a11y-alt-text");
          updateNotice.classList.add("a11y-update-notice--success");

          setTimeout(() => {
            updateNotice.classList.remove("a11y-update-notice--success");
          }, 3000);
        } else {
          let errorMessage = __(
            "Unable to generate alt text. Check error logs for details.",
            "a11y-alt-text"
          );

          if (response?.message) {
            errorMessage = response.message;
          }

          updateNotice.innerText = errorMessage;
          updateNotice.classList.add("a11y-update-notice--error");
        }

        // Reset button
        anchor.classList.remove("disabled", "a11y-processing");
        anchorLabel.innerHTML = __("Update Alt Text", "a11y-alt-text");
      },
      events: {
        ...previousAttachmentDetails.prototype.events,
        "click .a11y-generate-button__anchor": "A11YAnchorClick",
      },
      template: function (view) {
        // tmpl-attachment-details
        const html = previousAttachmentDetails.prototype.template.apply(
          this,
          arguments
        );
        const dom = document.createElement("div");
        dom.innerHTML = html;

        // Use the robust injection function
        injectGenerateButton(dom, view.model.id, "modal");
        return dom.innerHTML;
      },
    });
  }

  function showUrlAccessErrorNotification(message) {
    // Stop bulk processing
    window.a11y.setProcessingState(false);

    // Show Start Over button if there's a session to clear
    if (localStorage.getItem("a11y_bulk_progress")) {
      jQuery("#a11y-static-start-over-button").show();
    }

    // Update progress heading to show error
    if (window.a11y.progressHeading.length) {
      window.a11y.progressHeading.text(__("URL Access Error", "a11y-alt-text"));
    }

    // Create notification HTML with action button
    const notificationHtml = `
      <div class="a11y-url-access-notification bg-amber-900/5 p-px rounded-lg mb-6">
        <div class="bg-amber-50 rounded-lg p-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg class="size-5 mt-5 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3 flex-1">
              <h3 class="text-base font-semibold text-amber-800 mb-2">${__(
                "Image Access Problem",
                "a11y-alt-text"
              )}</h3>
              <p class="text-sm text-amber-700 mb-3">${__(
                "Some of your image URLs are not accessible to our servers. This can happen due to:",
                "a11y-alt-text"
              )}</p>
              <ul class="text-sm text-amber-700 mb-3 ml-4 list-disc space-y-1">
                <li>${__(
                  "Server firewalls or security restrictions",
                  "a11y-alt-text"
                )}</li>
                <li>${__(
                  "Local development environments (localhost)",
                  "a11y-alt-text"
                )}</li>
                <li>${__(
                  "Password-protected or staging sites",
                  "a11y-alt-text"
                )}</li>
                <li>${__(
                  "VPN or private network configurations",
                  "a11y-alt-text"
                )}</li>
              </ul>
              <p class="text-sm text-amber-800">${__(
                "Switching to direct upload mode will send your images securely to our servers instead of using URLs, which resolves this issue.",
                "a11y-alt-text"
              )}</p>
            </div>
          </div>
          <div class="mt-4 flex gap-3">
            <button type="button" id="a11y-fix-url-access" class="a11y-button blue">
              ${__("Update Setting Now", "a11y-alt-text")}
            </button>
            <button type="button" id="a11y-dismiss-url-notification" class="a11y-button white">
              ${__("Dismiss", "a11y-alt-text")}
            </button>
          </div>
        </div>
      </div>
    `;

    // Insert notification after the progress wrapper
    const progressWrapper = jQuery("[data-bulk-generate-progress-wrapper]");
    if (progressWrapper.length) {
      progressWrapper.after(notificationHtml);

      // Add event handlers
      jQuery("#a11y-fix-url-access").on("click", function () {
        // Update the setting via AJAX
        jQuery
          .post(
            wp_a11y.ajax_url,
            {
              action: "a11y_update_public_setting",
              security: wp_a11y.security_update_public_setting,
              a11y_public: "no",
            },
            function (response) {
              if (response.success) {
                // Reload page to reset the bulk generation with new setting
                window.location.reload();
              }
            }
          )
          .fail(function (xhr, status, error) {
            console.error("AJAX request failed:", error);
            // Fallback - just reload the page
            window.location.reload();
          });
      });

      jQuery("#a11y-dismiss-url-notification").on("click", function () {
        jQuery(".a11y-url-access-notification").remove();
      });
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    if (!wp?.media?.view?.Attachment?.Details) {
      return;
    }

    // Use a small delay to ensure WordPress media is fully initialized
    setTimeout(extendMediaTemplate, 500);
  });
})();
