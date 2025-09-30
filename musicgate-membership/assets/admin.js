// MusicGate Membership Admin Script
;(($) => {
  // Declare wp variable
  const wp = window.wp

  $(document).ready(() => {
    // Image upload functionality
    $(".mgm-upload-image").on("click", function (e) {
      e.preventDefault()

      const button = $(this)
      const input = button.prev('input[type="url"]')

      const mediaUploader = wp.media({
        title: "انتخاب تصویر",
        button: {
          text: "انتخاب",
        },
        multiple: false,
      })

      mediaUploader.on("select", () => {
        const attachment = mediaUploader.state().get("selection").first().toJSON()
        input.val(attachment.url)
      })

      mediaUploader.open()
    })
  })
})(window.jQuery)
