document.addEventListener('DOMContentLoaded', () => {
  // Problem: flatpickr appends its calendar to <body>. When a flatpickr input
  // is inside a Bootstrap dropdown, clicking the calendar (prev/next month,
  // selecting a day) is seen by Bootstrap as a click "outside" the dropdown,
  // so it closes immediately.
  //
  // Fix: intercept Bootstrap's hide.bs.dropdown event and cancel it when a
  // flatpickr calendar is currently open. This is the most reliable approach
  // because it works with Bootstrap's own event system rather than trying to
  // race against it with stopPropagation.

  document.addEventListener('hide.bs.dropdown', function (e) {
    // Is any flatpickr calendar currently open?
    var openCalendar = document.querySelector('.flatpickr-calendar.open');
    if (openCalendar) {
      // A flatpickr is open — block the dropdown from closing.
      e.preventDefault();
    }
  });
});
