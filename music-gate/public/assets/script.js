;(() => {
  var OVERLAY_SELECTOR = ".mg-content-overlay"
  var SUBSCRIBE_TRIGGER_SELECTOR = ".mg-subscribe-trigger"
  var SUBSCRIPTION_POPUP_SELECTOR = ".mg-subscription-popup"

  function isCheckoutPage() {
    var path = window.location.pathname
    var search = window.location.search
    return path.indexOf("/checkout") !== -1 || search.indexOf("woocommerce") !== -1
  }

  function init() {
    if (isCheckoutPage()) {
      return
    }

    function ensureOverlayExists() {
      var overlay = document.querySelector(OVERLAY_SELECTOR)
      if (!overlay) {
        overlay = document.createElement("div")
        overlay.className = "mg-content-overlay"
        overlay.innerHTML =
          '<div class="mg-overlay-inner"><h3>برای ادامه مطالعه اشتراک تهیه کنید</h3><p>برای دسترسی کامل به محتوا اشتراک لازم است</p><button class="mg-cta mg-subscribe-trigger">تهیه اشتراک</button></div>'
        document.body.appendChild(overlay)
      }
      return overlay
    }

    var overlay = ensureOverlayExists()
    var LOCK_PERCENT = 60

    if (window.mg_vars && window.mg_vars.lock_percentage) {
      LOCK_PERCENT = Number.parseInt(window.mg_vars.lock_percentage, 10)
    } else if (window.musicGate && window.musicGate.restrictionPercentage) {
      LOCK_PERCENT = Number.parseInt(window.musicGate.restrictionPercentage, 10)
    }

    function updateOverlayHeight() {
      var totalH = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight, window.innerHeight)
      var freeTop = Math.round((totalH * (100 - LOCK_PERCENT)) / 100)
      overlay.style.top = freeTop + "px"
      overlay.style.height = totalH - freeTop + "px"
      overlay.style.left = "0"
      overlay.style.width = "100%"
    }

    updateOverlayHeight()

    var start = Date.now()
    var poll = setInterval(() => {
      updateOverlayHeight()
      if (Date.now() - start > 10000) {
        clearInterval(poll)
        attachObservers()
      }
    }, 300)

    function attachObservers() {
      if (window.ResizeObserver) {
        try {
          var ro = new ResizeObserver(updateOverlayHeight)
          ro.observe(document.documentElement)
          ro.observe(document.body)
        } catch (e) {
          console.error("ResizeObserver error:", e)
        }
      } else {
        window.addEventListener("resize", updateOverlayHeight)
        window.addEventListener("scroll", updateOverlayHeight)
      }

      if (window.MutationObserver) {
        var mo = new MutationObserver(() => {
          updateOverlayHeight()
        })
        mo.observe(document.body, {
          childList: true,
          subtree: true,
          attributes: true,
        })
      }
    }

    setTimeout(() => {
      var popup =
        document.querySelector(SUBSCRIPTION_POPUP_SELECTOR) || document.querySelector("#mg-subscription-popup")
      if (popup) {
        openCenteredModalFromExistingPopup(popup)
      } else {
        openMgModalWithPlans()
      }
    }, 10000)

    overlay.addEventListener(
      "click",
      (e) => {
        if (e.target.closest(".mg-cta") || e.target.closest(".mg-overlay-inner")) {
          return
        }
        e.preventDefault()
        e.stopPropagation()
      },
      true,
    )

    document.addEventListener("click", (e) => {
      var btn = e.target.closest(SUBSCRIBE_TRIGGER_SELECTOR)
      if (!btn) {
        return
      }
      e.preventDefault()
      var popup =
        document.querySelector(SUBSCRIPTION_POPUP_SELECTOR) || document.querySelector("#mg-subscription-popup")
      if (popup) {
        openCenteredModalFromExistingPopup(popup)
      } else {
        openMgModalWithPlans()
      }
    })

    function openCenteredModalFromExistingPopup(popupElem) {
      var backdrop = document.querySelector(".mg-modal-backdrop")
      if (!backdrop) {
        backdrop = document.createElement("div")
        backdrop.className = "mg-modal-backdrop"
        backdrop.innerHTML = '<div class="mg-modal" role="dialog" aria-modal="true"></div>'
        document.body.appendChild(backdrop)
      }
      var modal = backdrop.querySelector(".mg-modal")
      modal.innerHTML = popupElem.innerHTML
      backdrop.style.display = "flex"
      modal.setAttribute("tabindex", "-1")
      modal.focus()

      backdrop.addEventListener("click", (ev) => {
        if (ev.target === backdrop) {
          backdrop.style.display = "none"
        }
      })

      var planButtons = modal.querySelectorAll("[data-plan]")
      for (var i = 0; i < planButtons.length; i++) {
        planButtons[i].addEventListener("click", function (e) {
          e.preventDefault()
          var plan = this.getAttribute("data-plan")
          if (window.mg_create_payment) {
            window.mg_create_payment(plan)
          } else if (window.mgPurchasePlan) {
            window.mgPurchasePlan(plan)
          }
        })
      }
    }

    function openMgModalWithPlans() {
      var html =
        "<h3>انتخاب پلن</h3>" +
        '<button data-plan="month1">1 ماه</button>' +
        '<button data-plan="month3">3 ماه</button>' +
        '<button data-plan="year1">1 سال</button>'
      var backdrop = document.querySelector(".mg-modal-backdrop")
      if (!backdrop) {
        backdrop = document.createElement("div")
        backdrop.className = "mg-modal-backdrop"
        backdrop.innerHTML = '<div class="mg-modal" role="dialog" aria-modal="true">' + html + "</div>"
        document.body.appendChild(backdrop)
      } else {
        backdrop.querySelector(".mg-modal").innerHTML = html
        backdrop.style.display = "flex"
      }
    }
  }

  window.mg_create_payment = (plan) => {
    var mg_vars = window.mg_vars || window.musicGate
    if (!mg_vars || !mg_vars.ajaxUrl) {
      console.error("mg_vars not configured.")
      return
    }
    var data = new FormData()
    data.append("action", "mg_create_payment")
    data.append("plan", plan)
    data.append("nonce", mg_vars.nonce)

    fetch(mg_vars.ajaxUrl, {
      method: "POST",
      body: data,
      credentials: "same-origin",
    })
      .then((r) => r.json())
      .then((json) => {
        console.log("Payment response:", json)
        if (json.success && json.data && json.data.redirect_url) {
          window.location.href = json.data.redirect_url
        } else {
          var msg = json.data && json.data.message ? json.data.message : "خطا در پرداخت. بعداً تلاش کنید."
          alert(msg)
        }
      })
      .catch((err) => {
        console.error("Payment error:", err)
        alert("خطای شبکه. دوباره تلاش کنید.")
      })
  }

  if (window.jQuery) {
    var $ = window.jQuery

    $(document).ready(() => {
      console.log("Music Gate initializing...")

      $(document).on("click", ".mg-show-register-popup", (e) => {
        e.preventDefault()
        $("#mg-register-popup").fadeIn(300)
      })

      $(document).on(
        "click",
        ".mg-show-subscription-popup, .mg-get-subscription, .mg-show-plans, .mg-purchase-link",
        (e) => {
          e.preventDefault()
          $("#mg-subscription-popup").fadeIn(300)
        },
      )

      $(document).on("click", ".mg-popup-close", function (e) {
        e.preventDefault()
        $(this).closest(".mg-popup-overlay").fadeOut(300)
      })

      $(document).on("click", ".mg-tab-btn", function () {
        var tab = $(this).data("tab")
        $(".mg-tab-btn").removeClass("active")
        $(this).addClass("active")
        $(".mg-tab-content").removeClass("active")
        $("#mg-" + tab + "-tab").addClass("active")
      })

      $(document).on("click", ".mg-purchase-plan, .mg-select-plan", function (e) {
        e.preventDefault()
        var planId = $(this).data("plan")
        if (planId) {
          if (window.mgPurchasePlan) {
            window.mgPurchasePlan(planId)
          } else if (window.mg_create_payment) {
            window.mg_create_payment(planId)
          }
        }
      })

      $(document).on("submit", "#mg-register-form", function (e) {
        e.preventDefault()
        var form = $(this)
        var submitBtn = form.find('button[type="submit"]')
        var originalText = submitBtn.text()

        submitBtn.html('<span class="mg-loading"></span> در حال ثبت نام...')
        submitBtn.prop("disabled", true)

        var formData = {
          action: "mg_register_user",
          nonce: window.musicGate.nonce,
          first_name: form.find('input[name="first_name"]').val(),
          last_name: form.find('input[name="last_name"]').val(),
          phone: form.find('input[name="phone"]').val(),
          password: form.find('input[name="password"]').val(),
        }

        $.post(window.musicGate.ajaxUrl, formData)
          .done((response) => {
            if (response.success) {
              location.reload()
            } else {
              alert(response.data || "خطا در ثبت نام")
            }
          })
          .fail(() => {
            alert("خطا در ارتباط با سرور")
          })
          .always(() => {
            submitBtn.text(originalText)
            submitBtn.prop("disabled", false)
          })
      })

      $(document).on("submit", "#mg-login-form", function (e) {
        e.preventDefault()
        var form = $(this)
        var submitBtn = form.find('button[type="submit"]')
        var originalText = submitBtn.text()

        submitBtn.html('<span class="mg-loading"></span> در حال ورود...')
        submitBtn.prop("disabled", true)

        var formData = {
          action: "mg_login_user",
          nonce: window.musicGate.nonce,
          phone: form.find('input[name="phone"]').val(),
          password: form.find('input[name="password"]').val(),
        }

        $.post(window.musicGate.ajaxUrl, formData)
          .done((response) => {
            if (response.success) {
              location.reload()
            } else {
              alert(response.data || "خطا در ورود")
            }
          })
          .fail(() => {
            alert("خطا در ارتباط با سرور")
          })
          .always(() => {
            submitBtn.text(originalText)
            submitBtn.prop("disabled", false)
          })
      })

      $(document).on("click", ".mg-dropdown-toggle", function (e) {
        e.preventDefault()
        $(this).closest(".mg-user-dropdown").toggleClass("open")
      })

      $(document).on("click", (e) => {
        if (!$(e.target).closest(".mg-user-dropdown").length) {
          $(".mg-user-dropdown").removeClass("open")
        }
      })

      $(document).keyup((e) => {
        if (e.keyCode === 27) {
          $(".mg-popup-overlay").fadeOut(300)
          $(".mg-modal-backdrop").hide()
        }
      })

      var urlParams = new URLSearchParams(window.location.search)
      if (urlParams.get("mg_success") === "1") {
        setTimeout(() => {
          alert("خوش آمدید! اشتراک شما با موفقیت فعال شد.")
          location.reload()
        }, 500)
      }
    })
  }

  init()
})()
