( function($) { 'use strict';
    $(document).ready(function() {

        var Front = {
          init: function () {
            this.signupbutton();
          },

          signupbutton: function () {
            if (
              $("body #buddypress #register-page #signup-form #legal_agreement")
                .length
            ) {
                ;
                $(
                  "body #buddypress #register-page #signup-form #legal_agreement"
                ).off("change", "**");
              $(
                "body #buddypress #register-page #signup-form .submit #signup_submit"
              ).prop("disabled", true);
              $(document).on(
                "change",
                "body #buddypress #register-page #signup-form #legal_agreement, body #buddypress #register-page #signup-form #community_guidelines_agreement",
                function () {
                  
                  if (
                    $(
                      "body #buddypress #register-page #signup-form #legal_agreement"
                    ).prop("checked") &&
                    $(
                      "body #buddypress #register-page #signup-form #community_guidelines_agreement"
                    ).prop("checked")
                  ) {
                    $(
                      "body #buddypress #register-page #signup-form .submit #signup_submit"
                    ).prop("disabled", false);
                  } else {
                    $(
                      "body #buddypress #register-page #signup-form .submit #signup_submit"
                    ).prop("disabled", true);
                  }
                }
              );
            }
          },
        };

        Front.init();
    });
})(jQuery);