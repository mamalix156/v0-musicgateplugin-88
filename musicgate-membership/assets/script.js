// MusicGate Membership Frontend Script
;(($) => {
  // Declare mgm_ajax variable
  const mgm_ajax = window.mgm_ajax || {}

  $(document).ready(() => {
    // Initialize content overlay if needed
    if (mgm_ajax.restriction_enabled === "1" && mgm_ajax.has_subscription === "0") {
      initContentOverlay()
    }
  })

  function initContentOverlay() {
    const overlay = $("#mgm-content-overlay")
    if (overlay.length === 0) return

    function updateOverlay() {
      const documentHeight = $(document).height()
      const windowHeight = $(window).height()
      const restrictionPercentage = Number.parseInt(mgm_ajax.restriction_percentage) || 60

      // Calculate overlay height
      const overlayHeight = (documentHeight * restrictionPercentage) / 100

      // Show overlay and set height
      overlay.css({
        height: overlayHeight + "px",
        display: "flex",
      })

      // Prevent scrolling past the visible area
      const maxScroll = documentHeight - windowHeight - overlayHeight + 100

      $(window).on("scroll", () => {
        if ($(window).scrollTop() > maxScroll) {
          $(window).scrollTop(maxScroll)
        }
      })
    }

    // Update overlay on page load and resize
    updateOverlay()
    $(window).on("resize", updateOverlay)

    // Update every few seconds for dynamic content
    setInterval(updateOverlay, 3000)

    // Monitor for content changes
    if (window.MutationObserver) {
      const observer = new MutationObserver((mutations) => {
        let shouldUpdate = false
        mutations.forEach((mutation) => {
          if (mutation.type === "childList" && mutation.addedNodes.length > 0) {
            shouldUpdate = true
          }
        })
        if (shouldUpdate) {
          setTimeout(updateOverlay, 500)
        }
      })

      observer.observe(document.body, {
        childList: true,
        subtree: true,
      })
    }
  }
})(window.jQuery)
