# Release acceptance checklist

Target: Fluent Forms 6.2.13; documentation revision `26cc985c69f521ec9e7c11f21f5fc64352c99805`; published feature catalog captured 2026-09-18T18:51:14.832Z.

**This is a development build, not the complete parity release.** Every item must be verified with evidence before a stable release. Partial implementation and local tests do not establish full reference parity. Provider mocks never satisfy provider acceptance.

Status: 309 not_implemented, 93 partial, 31 verified.

Run `npm run release:check` to enforce the gate. Read [current coverage and blockers](STATUS.md) and [verification evidence](VERIFICATION.md).

## account-license

- [ ] **setting up managers access control** — `doc:account-license/setting-up-managers-access-control` — not_implemented

## advanced-developer

- [ ] **mapping meta fields with meta keys** — `doc:advanced-developer/custom-fields-meta/mapping-meta-fields-with-meta-keys` — not_implemented
- [ ] **use acf to add a custom field in the post form** — `doc:advanced-developer/custom-fields-meta/use-acf-to-add-a-custom-field-in-the-post-form` — not_implemented
- [ ] **use meta box custom fields** — `doc:advanced-developer/custom-fields-meta/use-meta-box-custom-fields` — not_implemented
- [ ] **using jetengine custom fields** — `doc:advanced-developer/custom-fields-meta/using-jetengine-custom-fields` — not_implemented
- [ ] **translate datepicker field** — `doc:advanced-developer/localization/translate-datepicker-field` — not_implemented
- [ ] **translate forms with wpml** — `doc:advanced-developer/localization/translate-forms-with-wpml` — not_implemented
- [ ] **activity logs** — `doc:advanced-developer/logs-tracking/activity-logs` — not_implemented
- [ ] **api logs** — `doc:advanced-developer/logs-tracking/api-logs` — not_implemented
- [ ] **event tracking with google analytics ga4 via google tag manager** — `doc:advanced-developer/logs-tracking/event-tracking-with-google-analytics-ga4-via-google-tag-manager` — not_implemented
- [ ] **global search** — `doc:advanced-developer/logs-tracking/global-search` — not_implemented
- [ ] **phone field geo location provider** — `doc:advanced-developer/logs-tracking/phone-field-geo-location-provider` — not_implemented
- [ ] **conditional shortcodes** — `doc:advanced-developer/shortcodes/conditional-shortcodes` — not_implemented
- [ ] **form editor smart codes** — `doc:advanced-developer/shortcodes/form-editor-smart-codes` — not_implemented
- [ ] **pre fill form fields with fluentcrm data** — `doc:advanced-developer/shortcodes/pre-fill-form-fields-with-fluentcrm-data` — not_implemented
- [ ] **using helper shortcodes** — `doc:advanced-developer/shortcodes/using-helper-shortcodes` — not_implemented

## configuring-forms

- [ ] **add calc values on the repeat field** — `doc:configuring-forms/calculations/add-calc-values-on-the-repeat-field` — not_implemented
- [ ] **numeric calculation** — `doc:configuring-forms/calculations/numeric-calculation` — not_implemented
- [ ] **dynamic default value** — `doc:configuring-forms/conditional-logic/dynamic-default-value` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **dynamic input values in form steps** — `doc:configuring-forms/conditional-logic/dynamic-input-values-in-form-steps` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **set default form value from url parameters** — `doc:configuring-forms/conditional-logic/set-default-form-value-from-url-parameters` — not_implemented
- [ ] **set scroll offset of form steps** — `doc:configuring-forms/conditional-logic/set-scroll-offset-of-form-steps` — not_implemented
- [ ] **set up forms with conditional logic** — `doc:configuring-forms/conditional-logic/set-up-forms-with-conditional-logic` — not_implemented
- [ ] **unique field validation** — `doc:configuring-forms/conditional-logic/unique-field-validation` — not_implemented
- [ ] **error message customization** — `doc:configuring-forms/form-settings/error-message-customization` — not_implemented
- [ ] **form restrictions feature** — `doc:configuring-forms/form-settings/form-restrictions-feature` — not_implemented
- [ ] **form scheduling feature** — `doc:configuring-forms/form-settings/form-scheduling-feature` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **help message customization** — `doc:configuring-forms/form-settings/help-message-customization` — not_implemented
- [ ] **keyboard navigation shortcuts** — `doc:configuring-forms/form-settings/keyboard-navigation-shortcuts` — not_implemented
- [ ] **restrict blank form submission** — `doc:configuring-forms/form-settings/restrict-blank-form-submission` — not_implemented
- [ ] **user login requirement** — `doc:configuring-forms/form-settings/user-login-requirement` — not_implemented

## creating-forms

- [ ] **create a conversational form** — `doc:creating-forms/conversational-forms/create-a-conversational-form` — not_implemented
- [ ] **design a conversational form** — `doc:creating-forms/conversational-forms/design-a-conversational-form` — not_implemented
- [ ] **create a form with ai** — `doc:creating-forms/form-builder-basics/create-a-form-with-ai` — not_implemented
- [ ] **create a form with openai chatgpt** — `doc:creating-forms/form-builder-basics/create-a-form-with-openai-chatgpt` — not_implemented
- [ ] **create a form** — `doc:creating-forms/form-builder-basics/create-a-form` — not_implemented
- [ ] **using and customizing pre built quick forms** — `doc:creating-forms/form-builder-basics/using-and-customizing-pre-built-quick-forms` — not_implemented
- [ ] **create a post form** — `doc:creating-forms/specialized-form-types/create-a-post-form` — not_implemented
- [ ] **create a wordpress user registration form** — `doc:creating-forms/specialized-form-types/create-a-wordpress-user-registration-form` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **create a wordpress user update form** — `doc:creating-forms/specialized-form-types/create-a-wordpress-user-update-form` — not_implemented
- [ ] **creating a multi step form** — `doc:creating-forms/specialized-form-types/creating-a-multi-step-form` — not_implemented
- [ ] **creating a personality quiz form** — `doc:creating-forms/specialized-form-types/creating-a-personality-quiz-form` — not_implemented
- [ ] **inline opt in form** — `doc:creating-forms/specialized-form-types/inline-opt-in-form` — not_implemented
- [ ] **surveys and polls** — `doc:creating-forms/specialized-form-types/surveys-and-polls` — not_implemented

## design-styling

- [ ] **form layout settings** — `doc:design-styling/form-layout-settings` — not_implemented
- [ ] **modalpopuplightbox** — `doc:design-styling/modalpopuplightbox` — not_implemented
- [ ] **official form styler** — `doc:design-styling/official-form-styler` — not_implemented
- [ ] **stylingcustom css** — `doc:design-styling/stylingcustom-css` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **use css ready classes** — `doc:design-styling/use-css-ready-classes` — not_implemented

## form-fields

- [ ] **action hook field** — `doc:form-fields/advanced-fields/action-hook-field` — not_implemented
- [ ] **chained select field** — `doc:form-fields/advanced-fields/chained-select-field` — not_implemented
- [ ] **checkable grid input field** — `doc:form-fields/advanced-fields/checkable-grid-input-field` — not_implemented
- [ ] **color picker field** — `doc:form-fields/advanced-fields/color-picker-field` — not_implemented
- [ ] **custom submit button** — `doc:form-fields/advanced-fields/custom-submit-button` — not_implemented
- [ ] **dynamic field** — `doc:form-fields/advanced-fields/dynamic-field` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **fluentbooking field** — `doc:form-fields/advanced-fields/fluentbooking-field` — not_implemented
- [ ] **gdpr agreement field** — `doc:form-fields/advanced-fields/gdpr-agreement-field` — not_implemented
- [ ] **hidden input field** — `doc:form-fields/advanced-fields/hidden-input-field` — not_implemented
- [ ] **net promoter score** — `doc:form-fields/advanced-fields/net-promoter-score` — not_implemented
- [ ] **password input field** — `doc:form-fields/advanced-fields/password-input-field` — not_implemented
- [ ] **quiz score** — `doc:form-fields/advanced-fields/quiz-score` — not_implemented
- [ ] **range slider field** — `doc:form-fields/advanced-fields/range-slider-field` — not_implemented
- [ ] **ranking field** — `doc:form-fields/advanced-fields/ranking-field` — not_implemented
- [ ] **ratings input field** — `doc:form-fields/advanced-fields/ratings-input-field` — not_implemented
- [ ] **repeat input field** — `doc:form-fields/advanced-fields/repeat-input-field` — not_implemented
- [ ] **rich text input field** — `doc:form-fields/advanced-fields/rich-text-input-field` — not_implemented
- [ ] **save progress button** — `doc:form-fields/advanced-fields/save-progress-button` — not_implemented
- [ ] **section break** — `doc:form-fields/advanced-fields/section-break` — not_implemented
- [ ] **shortcode input field** — `doc:form-fields/advanced-fields/shortcode-input-field` — not_implemented
- [ ] **terms conditions field** — `doc:form-fields/advanced-fields/terms-conditions-field` — not_implemented
- [ ] **add accordion container fields** — `doc:form-fields/container-fields/add-accordion-container-fields` — not_implemented
- [ ] **add container fields** — `doc:form-fields/container-fields/add-container-fields` — not_implemented
- [ ] **add repeat container field** — `doc:form-fields/container-fields/add-repeat-container-field` — not_implemented
- [ ] **resizeable container** — `doc:form-fields/container-fields/resizeable-container` — not_implemented
- [ ] **adding a mask input field guide** — `doc:form-fields/general-fields/adding-a-mask-input-field-guide` — not_implemented
- [ ] **adding a simple text input field** — `doc:form-fields/general-fields/adding-a-simple-text-input-field` — not_implemented
- [ ] **adding a text area input field** — `doc:form-fields/general-fields/adding-a-text-area-input-field` — not_implemented
- [ ] **address input field** — `doc:form-fields/general-fields/address-input-field` — not_implemented
- [ ] **checkbox field** — `doc:form-fields/general-fields/checkbox-field` — not_implemented
- [ ] **country list field** — `doc:form-fields/general-fields/country-list-field` — not_implemented
- [ ] **custom html field** — `doc:form-fields/general-fields/custom-html-field` — not_implemented
- [ ] **dropdown field** — `doc:form-fields/general-fields/dropdown-field` — not_implemented
- [ ] **email address input field** — `doc:form-fields/general-fields/email-address-input-field` — not_implemented
- [ ] **file upload input field** — `doc:form-fields/general-fields/file-upload-input-field` — not_implemented
- [ ] **image upload input field** — `doc:form-fields/general-fields/image-upload-input-field` — not_implemented
- [ ] **multiple choice field** — `doc:form-fields/general-fields/multiple-choice-field` — not_implemented
- [ ] **name input field** — `doc:form-fields/general-fields/name-input-field` — not_implemented
- [ ] **numeric input field** — `doc:form-fields/general-fields/numeric-input-field` — not_implemented
- [ ] **phonemobile input field** — `doc:form-fields/general-fields/phonemobile-input-field` — not_implemented
- [ ] **radio field** — `doc:form-fields/general-fields/radio-field` — not_implemented
- [ ] **time date input field** — `doc:form-fields/general-fields/time-date-input-field` — not_implemented
- [ ] **website url input field guide** — `doc:form-fields/general-fields/website-url-input-field-guide` — not_implemented
- [ ] **add categories taxonomy field in post forms** — `doc:form-fields/post-taxonomy-fields/add-categories-taxonomy-field-in-post-forms` — not_implemented
- [ ] **add featured image field in post forms** — `doc:form-fields/post-taxonomy-fields/add-featured-image-field-in-post-forms` — not_implemented
- [ ] **add post content field in post forms** — `doc:form-fields/post-taxonomy-fields/add-post-content-field-in-post-forms` — not_implemented
- [ ] **add post excerpt field in post forms** — `doc:form-fields/post-taxonomy-fields/add-post-excerpt-field-in-post-forms` — not_implemented
- [ ] **add post title field in post forms** — `doc:form-fields/post-taxonomy-fields/add-post-title-field-in-post-forms` — not_implemented
- [ ] **add post update field in post forms** — `doc:form-fields/post-taxonomy-fields/add-post-update-field-in-post-forms` — not_implemented
- [ ] **add tags taxonomy field in post forms** — `doc:form-fields/post-taxonomy-fields/add-tags-taxonomy-field-in-post-forms` — not_implemented

## import-export-migration

- [ ] **import and export fluent forms** — `doc:import-export-migration/import-and-export-fluent-forms` — not_implemented
- [ ] **importing entries** — `doc:import-export-migration/importing-entries` — not_implemented
- [ ] **migrator wpforms contact form 7 gravity forms ninja forms caldera forms** — `doc:import-export-migration/migrator-wpforms-contact-form-7-gravity-forms-ninja-forms-caldera-forms` — not_implemented

## integrations

- [ ] **integrate platformly** — `doc:integrations/automation/integrate-platformly` — not_implemented
- [ ] **integrate webhook** — `doc:integrations/automation/integrate-webhook` — not_implemented
- [ ] **integrate zapier** — `doc:integrations/automation/integrate-zapier` — not_implemented
- [ ] **amocrm integration** — `doc:integrations/crm/amocrm-integration` — not_implemented
- [ ] **drip integration** — `doc:integrations/crm/drip-integration` — not_implemented
- [ ] **fluentcrm integration** — `doc:integrations/crm/fluentcrm-integration` — not_implemented
- [ ] **hubspot integration** — `doc:integrations/crm/hubspot-integration` — not_implemented
- [ ] **insightly integration** — `doc:integrations/crm/insightly-integration` — not_implemented
- [ ] **onepagecrm integration** — `doc:integrations/crm/onepagecrm-integration` — not_implemented
- [ ] **pipedrive integration** — `doc:integrations/crm/pipedrive-integration` — not_implemented
- [ ] **salesflare integration** — `doc:integrations/crm/salesflare-integration` — not_implemented
- [ ] **salesforce integration** — `doc:integrations/crm/salesforce-integration` — not_implemented
- [ ] **zoho crm integration** — `doc:integrations/crm/zoho-crm-integration` — not_implemented
- [ ] **automizy integration** — `doc:integrations/email-marketing/automizy-integration` — not_implemented
- [ ] **clicksend sms integration** — `doc:integrations/email-marketing/clicksend-sms-integration` — not_implemented
- [ ] **gist integration** — `doc:integrations/email-marketing/gist-integration` — not_implemented
- [ ] **integrate activecampaign** — `doc:integrations/email-marketing/integrate-activecampaign` — not_implemented
- [ ] **integrate brevo formerly sendinblue** — `doc:integrations/email-marketing/integrate-brevo-formerly-sendinblue` — not_implemented
- [ ] **integrate campaign monitor** — `doc:integrations/email-marketing/integrate-campaign-monitor` — not_implemented
- [ ] **integrate cleverreach** — `doc:integrations/email-marketing/integrate-cleverreach` — not_implemented
- [ ] **integrate constant contact** — `doc:integrations/email-marketing/integrate-constant-contact` — not_implemented
- [ ] **integrate getresponse** — `doc:integrations/email-marketing/integrate-getresponse` — not_implemented
- [ ] **integrate gist** — `doc:integrations/email-marketing/integrate-gist` — not_implemented
- [ ] **integrate icontact** — `doc:integrations/email-marketing/integrate-icontact` — not_implemented
- [ ] **integrate kit former convertkit** — `doc:integrations/email-marketing/integrate-kit-former-convertkit` — not_implemented
- [ ] **integrate mailchimp** — `doc:integrations/email-marketing/integrate-mailchimp` — not_implemented
- [ ] **integrate mailerlite** — `doc:integrations/email-marketing/integrate-mailerlite` — not_implemented
- [ ] **integrate mailjet** — `doc:integrations/email-marketing/integrate-mailjet` — not_implemented
- [ ] **integrate mailster** — `doc:integrations/email-marketing/integrate-mailster` — not_implemented
- [ ] **integrate moosend** — `doc:integrations/email-marketing/integrate-moosend` — not_implemented
- [ ] **integrate sendfox** — `doc:integrations/email-marketing/integrate-sendfox` — not_implemented
- [ ] **mailpoet integration** — `doc:integrations/email-marketing/mailpoet-integration` — not_implemented
- [ ] **mautic integration** — `doc:integrations/email-marketing/mautic-integration` — not_implemented
- [ ] **affiliatewp integration** — `doc:integrations/other-apps/affiliatewp-integration` — not_implemented
- [ ] **email validation with clearout** — `doc:integrations/other-apps/email-validation-with-clearout` — not_implemented
- [ ] **integrate airtable** — `doc:integrations/other-apps/integrate-airtable` — not_implemented
- [ ] **integrate buddyboss** — `doc:integrations/other-apps/integrate-buddyboss` — not_implemented
- [ ] **integrate google maps** — `doc:integrations/other-apps/integrate-google-maps` — not_implemented
- [ ] **integrate google sheets** — `doc:integrations/other-apps/integrate-google-sheets` — not_implemented
- [ ] **integrate notion** — `doc:integrations/other-apps/integrate-notion` — not_implemented
- [ ] **integrate openai chatgpt** — `doc:integrations/other-apps/integrate-openai-chatgpt` — not_implemented
- [ ] **integrate trello** — `doc:integrations/other-apps/integrate-trello` — not_implemented
- [ ] **twilio integration** — `doc:integrations/other-apps/twilio-integration` — not_implemented
- [ ] **integrate discord** — `doc:integrations/team-chat/integrate-discord` — not_implemented
- [ ] **integrate slack** — `doc:integrations/team-chat/integrate-slack` — not_implemented
- [ ] **integrate telegram** — `doc:integrations/team-chat/integrate-telegram` — not_implemented

## managing-submissions

- [ ] **add a date and time stamp to form entries** — `doc:managing-submissions/entries/add-a-date-and-time-stamp-to-form-entries` — not_implemented
- [ ] **admin approval feature** — `doc:managing-submissions/entries/admin-approval-feature` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **automatically delete form entries** — `doc:managing-submissions/entries/automatically-delete-form-entries` — not_implemented
- [ ] **edit history feature** — `doc:managing-submissions/entries/edit-history-feature` — not_implemented
- [ ] **edit user submitted entries** — `doc:managing-submissions/entries/edit-user-submitted-entries` — not_implemented
- [ ] **frontend entry view** — `doc:managing-submissions/entries/frontend-entry-view` — not_implemented
- [ ] **managing entries** — `doc:managing-submissions/entries/managing-entries` — not_implemented
- [ ] **partial entries for step forms** — `doc:managing-submissions/entries/partial-entries-for-step-forms` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **search and filter form entries** — `doc:managing-submissions/entries/search-and-filter-form-entries` — not_implemented
- [ ] **show entries in frontend using ninja tables** — `doc:managing-submissions/entries/show-entries-in-frontend-using-ninja-tables` — not_implemented
- [ ] **visual representation of form entries** — `doc:managing-submissions/entries/visual-representation-of-form-entries` — not_implemented
- [ ] **submission reports** — `doc:managing-submissions/reports/submission-reports` — not_implemented

## modules

- [ ] **global inventory manager** — `doc:modules/global-inventory-manager` — not_implemented
- [ ] **inventory module** — `doc:modules/inventory-module` — not_implemented
- [ ] **mcp for ai agents** — `doc:modules/mcp-for-ai-agents` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **pdf module** — `doc:modules/pdf-module` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **post selection module** — `doc:modules/post-selection-module` — not_implemented
- [ ] **quiz module** — `doc:modules/quiz-module` — not_implemented

## notifications-confirmations

- [ ] **conditional confirmation message** — `doc:notifications-confirmations/confirmations/conditional-confirmation-message` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **setup form submission confirmation message** — `doc:notifications-confirmations/confirmations/setup-form-submission-confirmation-message` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **shortcodes in confirmation settings** — `doc:notifications-confirmations/confirmations/shortcodes-in-confirmation-settings` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **conditional email notification** — `doc:notifications-confirmations/email-notifications/conditional-email-notification` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **conditional email routing** — `doc:notifications-confirmations/email-notifications/conditional-email-routing` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **not sending email confirmations** — `doc:notifications-confirmations/email-notifications/not-sending-email-confirmations` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **set up double opt in emails** — `doc:notifications-confirmations/email-notifications/set-up-double-opt-in-emails` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **setup adminuser email notifications** — `doc:notifications-confirmations/email-notifications/setup-adminuser-email-notifications` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **weekly email summary** — `doc:notifications-confirmations/email-notifications/weekly-email-summary` — partial; docs/STATUS.md — implemented subset and remaining limitations

## payments

- [ ] **configure payment settings** — `doc:payments/getting-started-with-payments/configure-payment-settings` — not_implemented
- [ ] **create a payment form** — `doc:payments/getting-started-with-payments/create-a-payment-form` — not_implemented
- [ ] **add coupon field in payment forms** — `doc:payments/payment-fields/add-coupon-field-in-payment-forms` — not_implemented
- [ ] **add custom payment amount field in payment forms** — `doc:payments/payment-fields/add-custom-payment-amount-field-in-payment-forms` — not_implemented
- [ ] **add item quantity field in payment forms** — `doc:payments/payment-fields/add-item-quantity-field-in-payment-forms` — not_implemented
- [ ] **add payment item field in payment forms** — `doc:payments/payment-fields/add-payment-item-field-in-payment-forms` — not_implemented
- [ ] **add payment method field in payment forms** — `doc:payments/payment-fields/add-payment-method-field-in-payment-forms` — not_implemented
- [ ] **add payment summary field in payment forms** — `doc:payments/payment-fields/add-payment-summary-field-in-payment-forms` — not_implemented
- [ ] **add subscription field in payment forms** — `doc:payments/payment-fields/add-subscription-field-in-payment-forms` — not_implemented
- [ ] **integrate authorizenet** — `doc:payments/payment-gateways/integrate-authorizenet` — not_implemented
- [ ] **integrate mollie** — `doc:payments/payment-gateways/integrate-mollie` — not_implemented
- [ ] **integrate paddle** — `doc:payments/payment-gateways/integrate-paddle` — not_implemented
- [ ] **integrate paypal** — `doc:payments/payment-gateways/integrate-paypal` — not_implemented
- [ ] **integrate paystack** — `doc:payments/payment-gateways/integrate-paystack` — not_implemented
- [ ] **integrate razorpay** — `doc:payments/payment-gateways/integrate-razorpay` — not_implemented
- [ ] **integrate square inline payment integration** — `doc:payments/payment-gateways/integrate-square-inline-payment-integration` — not_implemented
- [ ] **integrate stripe** — `doc:payments/payment-gateways/integrate-stripe` — not_implemented
- [ ] **payment reports** — `doc:payments/payment-reports/payment-reports` — not_implemented
- [ ] **Stripe: configuration and checkout** — `payment:stripe:configuration-and-checkout` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Stripe: server pricing and inventory** — `payment:stripe:server-pricing-and-inventory` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Stripe: callback verification and replay** — `payment:stripe:callback-verification-and-replay` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Stripe: renewals and subscriptions** — `payment:stripe:renewals-and-subscriptions` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Stripe: refunds and cancellations** — `payment:stripe:refunds-and-cancellations` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **PayPal: configuration and checkout** — `payment:paypal:configuration-and-checkout` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **PayPal: server pricing and inventory** — `payment:paypal:server-pricing-and-inventory` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **PayPal: callback verification and replay** — `payment:paypal:callback-verification-and-replay` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **PayPal: renewals and subscriptions** — `payment:paypal:renewals-and-subscriptions` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **PayPal: refunds and cancellations** — `payment:paypal:refunds-and-cancellations` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Mollie: configuration and checkout** — `payment:mollie:configuration-and-checkout` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Mollie: server pricing and inventory** — `payment:mollie:server-pricing-and-inventory` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Mollie: callback verification and replay** — `payment:mollie:callback-verification-and-replay` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Mollie: renewals and subscriptions** — `payment:mollie:renewals-and-subscriptions` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Mollie: refunds and cancellations** — `payment:mollie:refunds-and-cancellations` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Razorpay: configuration and checkout** — `payment:razorpay:configuration-and-checkout` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Razorpay: server pricing and inventory** — `payment:razorpay:server-pricing-and-inventory` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Razorpay: callback verification and replay** — `payment:razorpay:callback-verification-and-replay` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Razorpay: renewals and subscriptions** — `payment:razorpay:renewals-and-subscriptions` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Razorpay: refunds and cancellations** — `payment:razorpay:refunds-and-cancellations` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Square: configuration and checkout** — `payment:square:configuration-and-checkout` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Square: server pricing and inventory** — `payment:square:server-pricing-and-inventory` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Square: callback verification and replay** — `payment:square:callback-verification-and-replay` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Square: renewals and subscriptions** — `payment:square:renewals-and-subscriptions` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Square: refunds and cancellations** — `payment:square:refunds-and-cancellations` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Paddle: configuration and checkout** — `payment:paddle:configuration-and-checkout` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Paddle: server pricing and inventory** — `payment:paddle:server-pricing-and-inventory` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Paddle: callback verification and replay** — `payment:paddle:callback-verification-and-replay` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Paddle: renewals and subscriptions** — `payment:paddle:renewals-and-subscriptions` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Paddle: refunds and cancellations** — `payment:paddle:refunds-and-cancellations` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Paystack: configuration and checkout** — `payment:paystack:configuration-and-checkout` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Paystack: server pricing and inventory** — `payment:paystack:server-pricing-and-inventory` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Paystack: callback verification and replay** — `payment:paystack:callback-verification-and-replay` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Paystack: renewals and subscriptions** — `payment:paystack:renewals-and-subscriptions` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Paystack: refunds and cancellations** — `payment:paystack:refunds-and-cancellations` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Authorize.Net: configuration and checkout** — `payment:authorize.net:configuration-and-checkout` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Authorize.Net: server pricing and inventory** — `payment:authorize.net:server-pricing-and-inventory` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Authorize.Net: callback verification and replay** — `payment:authorize.net:callback-verification-and-replay` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Authorize.Net: renewals and subscriptions** — `payment:authorize.net:renewals-and-subscriptions` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.
- [ ] **Authorize.Net: refunds and cancellations** — `payment:authorize.net:refunds-and-cancellations` — not_implemented; blocker: Checkout, inventory and provider implementation are outstanding; real sandbox verification is also required.

## publishing-embedding

- [ ] **dedicated landing page** — `doc:publishing-embedding/dedicated-landing-page` — not_implemented
- [ ] **embed your forms directly in gutenberg layout** — `doc:publishing-embedding/embed-your-forms-directly-in-gutenberg-layout` — not_implemented
- [ ] **embed your forms using elementor widget** — `doc:publishing-embedding/embed-your-forms-using-elementor-widget` — not_implemented
- [ ] **oxygen builder widget** — `doc:publishing-embedding/oxygen-builder-widget` — not_implemented
- [ ] **use your forms as widget on your sidebar or footer** — `doc:publishing-embedding/use-your-forms-as-widget-on-your-sidebar-or-footer` — not_implemented

## security-spam

- [ ] **hcaptcha** — `doc:security-spam/hcaptcha` — not_implemented
- [ ] **integrate akismet** — `doc:security-spam/integrate-akismet` — not_implemented
- [ ] **integrate cloudflare turnstile** — `doc:security-spam/integrate-cloudflare-turnstile` — not_implemented
- [ ] **integrate hcaptcha** — `doc:security-spam/integrate-hcaptcha` — not_implemented
- [ ] **integrate recaptcha** — `doc:security-spam/integrate-recaptcha` — partial; evidence: docs/VERIFICATION.md — v2 Checkbox keys, rendering, and server verification exercised; v3 coverage remains
- [ ] **recaptcha field** — `doc:security-spam/recaptcha-field` — partial; evidence: docs/VERIFICATION.md — v2 Checkbox field, theme and size controls exercised
- [ ] **spam protection with honeypot and google recaptcha** — `doc:security-spam/spam-protection-with-honeypot-and-google-recaptcha` — partial; evidence: docs/SECURITY.md — honeypot and v2 Checkbox implemented; broader anti-spam coverage remains

## native-connectors

- [ ] **Mailchimp** — `native:mailchimp` — partial; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Slack** — `native:slack` — partial; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **PayPal** — `native:paypal` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Stripe** — `native:stripe` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **WebHooks** — `native:webhooks` — partial; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Zapier** — `native:zapier` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Trello** — `native:trello` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Google Sheet** — `native:google-sheet` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **ActiveCampaign** — `native:activecampaign` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Campaign Monitor** — `native:campaign-monitor` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Constant Contact** — `native:constant-contact` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **ConvertKit** — `native:convertkit` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **GetResponse** — `native:getresponse` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Hubspot** — `native:hubspot` — partial; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **iContact** — `native:icontact` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Platformly** — `native:platformly` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **MooSend** — `native:moosend` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **SendFox** — `native:sendfox` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **MailerLite** — `native:mailerlite` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **SMS Notification** — `native:sms-notification` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Gist** — `native:gist` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Brevo (formerly SendInBlue)** — `native:brevo-formerly-sendinblue-` — partial; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Drip** — `native:drip` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Discord** — `native:discord` — partial; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Telegram** — `native:telegram` — partial; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **AffiliateWP** — `native:affiliatewp` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **ClickSend** — `native:clicksend` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Zoho CRM** — `native:zoho-crm` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **CleverReach** — `native:cleverreach` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Salesflare** — `native:salesflare` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Automizy** — `native:automizy` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Salesforce** — `native:salesforce` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Airtable** — `native:airtable` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Mailjet** — `native:mailjet` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Pipedrive** — `native:pipedrive` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **amoCRM** — `native:amocrm` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **OnePageCRM** — `native:onepagecrm` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Insightly** — `native:insightly` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Mailster** — `native:mailster` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **Notion** — `native:notion` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.
- [ ] **FluentCRM across domains** — `doc:account-license/connecting-forms-with-fluent-crm-across-domains` — not_implemented; blocker: Dedicated provider coverage and real controlled-account verification are incomplete.

## foundation

- [x] **independent plugin and builds** — `foundation:independent-plugin-and-builds` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **versioned schema and migrations** — `foundation:versioned-schema-and-migrations` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **deactivation preserves data** — `foundation:deactivation-preserves-data` — verified; docs/VERIFICATION.md — version matrix, packaged-install/lifecycle checks and deterministic archive verification
- [x] **explicit uninstall deletion** — `foundation:explicit-uninstall-deletion` — verified; docs/VERIFICATION.md — version matrix, packaged-install/lifecycle checks and deterministic archive verification
- [x] **wordpress minimum and current** — `foundation:wordpress-minimum-and-current` — verified; docs/VERIFICATION.md — version matrix, packaged-install/lifecycle checks and deterministic archive verification
- [x] **php minimum and current** — `foundation:php-minimum-and-current` — verified; docs/VERIFICATION.md — version matrix, packaged-install/lifecycle checks and deterministic archive verification
- [x] **mysql production transactions** — `foundation:mysql-production-transactions` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence

## builder

- [ ] **reference screen comparison** — `builder:reference-screen-comparison` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **palette drag and drop** — `builder:palette-drag-and-drop` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **nested layout and reorder** — `builder:nested-layout-and-reorder` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **field duplication** — `builder:field-duplication` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **template inventory** — `builder:template-inventory` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **search and organization** — `builder:search-and-organization` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **undo redo** — `builder:undo-redo` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **revision restoration** — `builder:revision-restoration` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **keyboard and focus** — `builder:keyboard-and-focus` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **responsive preview** — `builder:responsive-preview` — partial; docs/STATUS.md — implemented subset and remaining limitations

## gitpress

- [x] **direct shortcode** — `gitpress:direct-shortcode` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **theme wrapped** — `gitpress:theme-wrapped` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **full canvas** — `gitpress:full-canvas` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **managed layout** — `gitpress:managed-layout` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **cached and stale fragments** — `gitpress:cached-and-stale-fragments` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **late shortcode assets** — `gitpress:late-shortcode-assets` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **multiple form instances** — `gitpress:multiple-form-instances` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **expired token renewal** — `gitpress:expired-token-renewal` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **optional dependency** — `gitpress:optional-dependency` — verified; docs/VERIFICATION.md — version matrix, packaged-install/lifecycle checks and deterministic archive verification

## security

- [x] **management capabilities** — `security:management-capabilities` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **per form permissions** — `security:per-form-permissions` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **protected upload download** — `security:protected-upload-download` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [ ] **malicious input and xss** — `security:malicious-input-and-xss` — not_implemented
- [x] **password redaction** — `security:password-redaction` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **server calculation authority** — `security:server-calculation-authority` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **idempotent submissions** — `security:idempotent-submissions` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [ ] **concurrent inventory** — `security:concurrent-inventory` — not_implemented
- [x] **encrypted credentials** — `security:encrypted-credentials` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **job recovery and retries** — `security:job-recovery-and-retries` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [ ] **privacy export erasure** — `security:privacy-export-erasure` — partial; docs/STATUS.md — implemented subset and remaining limitations

## migration

- [ ] **fluent definitions and layout** — `migration:fluent-definitions-and-layout` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **fluent style and settings** — `migration:fluent-style-and-settings` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **fluent entries and attachments** — `migration:fluent-entries-and-attachments` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [x] **preview and unmapped report** — `migration:preview-and-unmapped-report` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [x] **repeat import id mapping** — `migration:repeat-import-id-mapping` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [ ] **historical payments no charges** — `migration:historical-payments-no-charges` — not_implemented
- [ ] **credential reconnection** — `migration:credential-reconnection` — not_implemented
- [ ] **wpforms** — `migration:wpforms` — not_implemented
- [ ] **contact form 7** — `migration:contact-form-7` — not_implemented
- [ ] **gravity forms** — `migration:gravity-forms` — not_implemented
- [ ] **ninja forms** — `migration:ninja-forms` — not_implemented
- [ ] **caldera forms** — `migration:caldera-forms` — not_implemented

## delivery

- [x] **editable source** — `delivery:editable-source` — verified; docs/VERIFICATION.md — version matrix, packaged-install/lifecycle checks and deterministic archive verification
- [x] **reproducible zip** — `delivery:reproducible-zip` — verified; docs/VERIFICATION.md — version matrix, packaged-install/lifecycle checks and deterministic archive verification
- [x] **example forms** — `delivery:example-forms` — verified; docs/VERIFICATION.md — version matrix, packaged-install/lifecycle checks and deterministic archive verification
- [x] **installation and migration guide** — `delivery:installation-and-migration-guide` — verified; docs/VERIFICATION.md — version matrix, packaged-install/lifecycle checks and deterministic archive verification
- [x] **parent website checks** — `delivery:parent-website-checks` — verified; docs/VERIFICATION.md — automated browser, PHP/MySQL and build evidence
- [ ] **complete acceptance evidence** — `delivery:complete-acceptance-evidence` — not_implemented

## published-catalog

- [ ] **Drag and drop builder** — `catalog:drag-and-drop-builder` — not_implemented
- [ ] **65+ field inventory** — `catalog:65-field-inventory` — not_implemented
- [ ] **Template inventory** — `catalog:template-inventory` — not_implemented
- [ ] **Numeric calculations** — `catalog:numeric-calculations` — not_implemented
- [ ] **File and image uploads** — `catalog:file-and-image-uploads` — not_implemented
- [ ] **Multi-step forms** — `catalog:multi-step-forms` — not_implemented
- [ ] **Conversational forms** — `catalog:conversational-forms` — not_implemented
- [ ] **Post creation** — `catalog:post-creation` — not_implemented
- [ ] **Conditional logic** — `catalog:conditional-logic` — not_implemented
- [ ] **Payments** — `catalog:payments` — not_implemented
- [ ] **Address autocomplete** — `catalog:address-autocomplete` — not_implemented
- [ ] **Spam protection** — `catalog:spam-protection` — not_implemented
- [ ] **Quiz and survey** — `catalog:quiz-and-survey` — not_implemented
- [ ] **Advanced form styling** — `catalog:advanced-form-styling` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **Multiple columns** — `catalog:multiple-columns` — not_implemented
- [ ] **Custom CSS and JS** — `catalog:custom-css-and-js` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **Landing pages** — `catalog:landing-pages` — not_implemented
- [ ] **Reporting dashboard** — `catalog:reporting-dashboard` — not_implemented
- [ ] **Form import and export** — `catalog:form-import-and-export` — not_implemented
- [ ] **Entry export formats** — `catalog:entry-export-formats` — not_implemented
- [ ] **Native integrations** — `catalog:native-integrations` — not_implemented
- [ ] **PDF documents** — `catalog:pdf-documents` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **AI form generation** — `catalog:ai-form-generation` — not_implemented
- [ ] **Migration formats** — `catalog:migration-formats` — not_implemented
- [ ] **WP-CLI commands** — `catalog:wp-cli-commands` — not_implemented
- [ ] **GDPR agreement** — `catalog:gdpr-agreement` — not_implemented
- [ ] **Developer hooks** — `catalog:developer-hooks` — not_implemented
- [ ] **Save and resume** — `catalog:save-and-resume` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **Partial entries** — `catalog:partial-entries` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **Form scheduling** — `catalog:form-scheduling` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **Double opt-in** — `catalog:double-opt-in` — not_implemented
- [ ] **Empty submission prevention** — `catalog:empty-submission-prevention` — not_implemented
- [ ] **Visual reports** — `catalog:visual-reports` — not_implemented
- [ ] **Weekly email summaries** — `catalog:weekly-email-summaries` — not_implemented
- [ ] **Coupons** — `catalog:coupons` — not_implemented
- [ ] **Dynamic fields** — `catalog:dynamic-fields` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **Entry printing** — `catalog:entry-printing` — not_implemented
- [ ] **Advanced entry filters** — `catalog:advanced-entry-filters` — not_implemented
- [ ] **Revision history** — `catalog:revision-history` — not_implemented
- [ ] **Role management** — `catalog:role-management` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **Repeat fields** — `catalog:repeat-fields` — not_implemented
- [ ] **Admin approval** — `catalog:admin-approval` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **User registration** — `catalog:user-registration` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **Inventory** — `catalog:inventory` — not_implemented
- [ ] **Email routing and CC BCC** — `catalog:email-routing-and-cc-bcc` — not_implemented
- [ ] **Conditional confirmations** — `catalog:conditional-confirmations` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **Form location discovery** — `catalog:form-location-discovery` — not_implemented
- [ ] **Advanced validation** — `catalog:advanced-validation` — not_implemented
- [ ] **Retention** — `catalog:retention` — not_implemented
- [ ] **Responsive forms** — `catalog:responsive-forms` — not_implemented

## reference-fields

- [ ] **input name** — `field:input_name` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **input text** — `field:input_text` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **input email** — `field:input_email` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **textarea** — `field:textarea` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **address** — `field:address` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **select country** — `field:select_country` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **input number** — `field:input_number` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **select** — `field:select` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **input radio** — `field:input_radio` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **input checkbox** — `field:input_checkbox` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **input url** — `field:input_url` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **input date** — `field:input_date` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **input image** — `field:input_image` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **input file** — `field:input_file` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **custom html** — `field:custom_html` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **ratings** — `field:ratings` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **input hidden** — `field:input_hidden` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **tabular grid** — `field:tabular_grid` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **section break** — `field:section_break` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **input password** — `field:input_password` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **form step** — `field:form_step` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **terms and condition** — `field:terms_and_condition` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **gdpr agreement** — `field:gdpr_agreement` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **recaptcha** — `field:recaptcha` — partial; docs/VERIFICATION.md — v2 Checkbox field verified; other reference modes remain
- [ ] **hcaptcha** — `field:hcaptcha` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **turnstile** — `field:turnstile` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **shortcode** — `field:shortcode` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **action hook** — `field:action_hook` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **container** — `field:container` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **phone** — `field:phone` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **net promoter score** — `field:net_promoter_score` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **quiz score** — `field:quiz_score` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **dynamic field** — `field:dynamic_field` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **cpt selection** — `field:cpt_selection` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **save progress button** — `field:save_progress_button` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **rich text input** — `field:rich_text_input` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **chained select** — `field:chained_select` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **repeater field** — `field:repeater_field` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **rangeslider** — `field:rangeslider` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **input ranking** — `field:input_ranking` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **color-picker** — `field:color-picker` — partial; docs/STATUS.md — implemented subset and remaining limitations
- [ ] **accordion** — `field:accordion` — partial; docs/STATUS.md — implemented subset and remaining limitations
