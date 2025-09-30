/**
 * Music Gate Admin JavaScript
 */

const jQuery = window.jQuery
jQuery(document).ready(($) => {
  // Admin functionality placeholder
  console.log("Music Gate Admin loaded")

  // Handle form submissions
  $(".mg-admin-form").on("submit", function (e) {
    var $form = $(this)
    var $submit = $form.find('input[type="submit"]')

    $submit.prop("disabled", true)

    setTimeout(() => {
      $submit.prop("disabled", false)
    }, 2000)
  })

  // Confirmation dialogs for dangerous actions
  $(".mg-admin-btn-danger").on("click", (e) => {
    if (!confirm("Are you sure you want to perform this action?")) {
      e.preventDefault()
      return false
    }
  })
})
