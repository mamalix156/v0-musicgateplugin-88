// Admin JavaScript for Music Gate plugin
;(($) => {
  $(document).ready(() => {
    initializeAdmin()
  })

  function initializeAdmin() {
    // Add any admin-specific JavaScript functionality here
    console.log("Music Gate Admin initialized")

    // Handle form submissions with loading states
    $("form").on("submit", function () {
      const submitBtn = $(this).find('input[type="submit"]')
      const originalValue = submitBtn.val()

      submitBtn.val("در حال ذخیره...")
      submitBtn.prop("disabled", true)

      // Re-enable after a delay (form will submit)
      setTimeout(() => {
        submitBtn.val(originalValue)
        submitBtn.prop("disabled", false)
      }, 2000)
    })
  }
})(window.jQuery)
