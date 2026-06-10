document.addEventListener('DOMContentLoaded', () => {
  if (typeof window.flatpickr !== 'function') return;

  // ── German locale ──
  const German = {
    weekdays: {
      shorthand: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
      longhand: ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"],
    },
    months: {
      shorthand: ["Jan", "Feb", "Mär", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez"],
      longhand: ["Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"],
    },
    firstDayOfWeek: 1,
    rangeSeparator: " bis ",
    weekAbbreviation: "KW",
    scrollTitle: "Zum Ändern scrollen",
    toggleTitle: "Zum Umschalten klicken",
  };

  // Set German as default locale
  window.flatpickr.localize(German);

  // ── Standard flatpickr config ──
  var baseConfig = {
    dateFormat: 'Y-m-d',
    allowInput: true,
    disableMobile: true,
    locale: German,
  };

  // ── Linked date pairs (Von/Bis) ──
  function setupLinkedPairs() {
    // Method 1: Explicit data attributes
    document.querySelectorAll('[data-linked-dates]').forEach(function (container) {
      var fromInput = container.querySelector('[data-link-from]');
      var toInput = container.querySelector('[data-link-to]');
      if (fromInput && toInput) {
        linkDatePair(fromInput, toInput);
      }
    });

    // Method 2: Auto-detect common name patterns (from/to, von/bis)
    var autoPatterns = [
      { from: 'input[name="from"]', to: 'input[name="to"]' },
      { from: 'input[name="von"]', to: 'input[name="bis"]' },
    ];

    autoPatterns.forEach(function (pattern) {
      var fromInputs = document.querySelectorAll(pattern.from + '[type="date"]');
      fromInputs.forEach(function (fromInput) {
        var form = fromInput.closest('form') || fromInput.parentElement.parentElement;
        var toInput = form ? form.querySelector(pattern.to + '[type="date"]') : null;
        if (toInput && !fromInput._linkedDone) {
          linkDatePair(fromInput, toInput);
        }
      });
    });
  }

  function linkDatePair(fromInput, toInput) {
    // Skip if already handled by reservations-form.js
    if (fromInput.name === 'start_date' || toInput.name === 'end_date') return;
    // Mark as linked
    fromInput._linkedDone = true;
    toInput._linkedDone = true;

    var fpTo; // forward-declared so onChange can reference it

    var fromConfig = Object.assign({}, baseConfig, {
      onChange: function (selectedDates) {
        if (selectedDates.length > 0) {
          var selectedDate = selectedDates[0];
          fpTo.set('minDate', selectedDate);

          var currentTo = fpTo.selectedDates[0];
          if (currentTo && currentTo < selectedDate) {
            fpTo.clear();
          }
        } else {
          fpTo.set('minDate', null);
        }
      },
    });

    var fpFrom = window.flatpickr(fromInput, fromConfig);
    fpTo = window.flatpickr(toInput, Object.assign({}, baseConfig));

    // If Von already has a value, set initial minDate on Bis
    if (fromInput.value) {
      fpTo.set('minDate', fromInput.value);
    }

    fromInput.type = 'text';
    toInput.type = 'text';
  }

  // Run linked pair setup FIRST so those inputs get marked before generic init
  setupLinkedPairs();

  // ── Apply flatpickr to ALL remaining date inputs site-wide ──
  var dateInputs = document.querySelectorAll('input[type="date"]');

  dateInputs.forEach(function (input) {
    // Skip reservation form date fields — reservations-form.js handles those
    if (input.name === 'start_date' || input.name === 'end_date') return;
    // Skip already linked inputs
    if (input._linkedDone) return;

    window.flatpickr(input, Object.assign({}, baseConfig));
    input.type = 'text';
  });
});
