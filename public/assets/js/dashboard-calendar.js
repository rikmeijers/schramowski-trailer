document.addEventListener("DOMContentLoaded", function () {
    var calendarEl = document.getElementById("calendar-view");
    if (!calendarEl) return;

    var apiUrl = calendarEl.dataset.apiUrl;
    var detailUrlTemplate = calendarEl.dataset.detailUrl;
    var today = new Date();
    today.setHours(0, 0, 0, 0);

    var WEEKS_BACK = 4;
    var WEEKS_FORWARD = 8;
    var LOAD_THRESHOLD = 200;
    var loading = false;

    var rangeStart = getMonday(addDays(today, -WEEKS_BACK * 7));
    var rangeEnd = addDays(getMonday(today), WEEKS_FORWARD * 7);

    var cachedTrailers = [];
    var cachedReservations = [];

    var container = calendarEl.closest(".calendar-container");
    var btnToday = document.getElementById("cal-today");

    if (btnToday) btnToday.addEventListener("click", scrollToToday);

    if (container) {
        container.addEventListener("scroll", onScroll);
    }

    function getMonday(d) {
        d = new Date(d);
        var day = d.getDay();
        var diff = d.getDate() - day + (day === 0 ? -6 : 1);
        return new Date(d.setDate(diff));
    }

    function addDays(d, n) {
        var r = new Date(d);
        r.setDate(r.getDate() + n);
        return r;
    }

    function formatDate(d) {
        return d.getFullYear() + "-" +
            String(d.getMonth() + 1).padStart(2, "0") + "-" +
            String(d.getDate()).padStart(2, "0");
    }

    function formatDateShort(d) {
        return String(d.getDate()).padStart(2, "0") + "." +
            String(d.getMonth() + 1).padStart(2, "0");
    }

    function dayName(d) {
        var names = ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"];
        return names[d.getDay()];
    }

    function getDays(start, end) {
        var days = [];
        var d = new Date(start);
        while (d < end) {
            days.push(new Date(d));
            d.setDate(d.getDate() + 1);
        }
        return days;
    }

    function fetchAndRender() {
        if (loading) return;
        loading = true;

        var url = apiUrl + "?from=" + formatDate(rangeStart) + "&to=" + formatDate(rangeEnd);

        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                cachedTrailers = data.trailers;
                cachedReservations = data.reservations;
                renderGrid();
                loading = false;
            })
            .catch(function () {
                calendarEl.innerHTML = '<div class="text-danger p-3">Fehler beim Laden der Kalenderdaten.</div>';
                loading = false;
            });
    }

    function renderGrid() {
        var days = getDays(rangeStart, rangeEnd);
        if (days.length === 0) return;

        var minWidth = 180 + (days.length * 52);
        var html = '<div class="calendar-grid" style="grid-template-columns: max-content repeat(' + days.length + ', minmax(48px, 1fr)); min-width:' + minWidth + 'px;">';

        // Header: week separators + day cells
        html += '<div class="calendar-header-row">';
        html += '<div class="calendar-header-cell" style="text-align:left;">Anhänger</div>';

        for (var i = 0; i < days.length; i++) {
            var d = days[i];
            var isToday = d.getTime() === today.getTime();
            var isMonday = d.getDay() === 1;
            var cls = "calendar-header-cell";
            if (isToday) cls += " today";
            if (isMonday && i > 0) cls += " week-start";
            html += '<div class="' + cls + '">' + dayName(d) + '<br>' + formatDateShort(d) + '</div>';
        }
        html += '</div>';

        // Reservation lookup
        var resByTrailer = {};
        for (var r = 0; r < cachedReservations.length; r++) {
            var res = cachedReservations[r];
            if (!resByTrailer[res.trailer_id]) resByTrailer[res.trailer_id] = [];
            resByTrailer[res.trailer_id].push(res);
        }

        // Trailer rows
        for (var t = 0; t < cachedTrailers.length; t++) {
            var trailer = cachedTrailers[t];
            html += '<div class="calendar-row">';
            html += '<div class="calendar-trailer-label">' + escapeHtml(trailer.name) + '</div>';

            var trailerRes = resByTrailer[trailer.id] || [];

            for (var di = 0; di < days.length; di++) {
                var day = days[di];
                var isToday2 = day.getTime() === today.getTime();
                var isWeekend2 = day.getDay() === 0 || day.getDay() === 6;
                var isMonday2 = day.getDay() === 1 && di > 0;
                var cellCls = "calendar-cell";
                if (isToday2) cellCls += " today";
                if (isWeekend2) cellCls += " weekend";
                if (isMonday2) cellCls += " week-start";

                html += '<div class="' + cellCls + '">';

                for (var ri = 0; ri < trailerRes.length; ri++) {
                    var rv = trailerRes[ri];
                    var rvStart = new Date(rv.start_date);
                    rvStart.setHours(0, 0, 0, 0);
                    var rvEnd = new Date(rv.end_date);
                    rvEnd.setHours(0, 0, 0, 0);

                    if (day >= rvStart && day < rvEnd) {
                        var isStart = day.getTime() === rvStart.getTime();
                        var visStart = rvStart < rangeStart ? rangeStart : rvStart;
                        var isVisStart = day.getTime() === visStart.getTime();

                        if (isVisStart) {
                            var endIdx = days.length;
                            for (var si = di; si < days.length; si++) {
                                if (days[si] >= rvEnd) { endIdx = si; break; }
                            }
                            var span = endIdx - di;
                            if (span < 1) span = 1;

                            var barCls = "calendar-bar";
                            barCls += rv.status === "pending" ? " calendar-bar--pending" : " calendar-bar--confirmed";
                            if (!isStart) barCls += " calendar-bar--clipped-start";

                            var rvEndMinusOne = addDays(rvEnd, -1);
                            var barLabel = escapeHtml(rv.customer_last_name);
                            var titleText = rv.customer_first_name + " " + rv.customer_last_name + " | " +
                                formatDateShort(rvStart) + " – " + formatDateShort(rvEndMinusOne);

                            var detailUrl = detailUrlTemplate.replace("__ID__", rv.id);
                            var barWidth = "calc(" + (span * 100) + "% + " + ((span - 1)) + "px)";

                            html += '<a href="' + detailUrl + '" class="' + barCls + '" title="' + escapeHtml(titleText) + '" style="width:' + barWidth + '; z-index:2;">' + barLabel + '</a>';
                        }
                    }
                }

                html += '</div>';
            }
            html += '</div>';
        }

        html += '</div>';
        calendarEl.innerHTML = html;

        requestAnimationFrame(scrollToToday);
    }

    function scrollToToday() {
        if (!container) return;
        var todayCell = calendarEl.querySelector(".calendar-cell.today");
        if (!todayCell) {
            container.scrollLeft = 0;
            return;
        }
        var gridLeft = calendarEl.querySelector(".calendar-grid").getBoundingClientRect().left;
        var cellLeft = todayCell.getBoundingClientRect().left;
        var offset = cellLeft - gridLeft;
        var labelWidth = 180;
        container.scrollLeft = offset - labelWidth - 100;
    }

    function onScroll() {
        if (loading) return;
        if (!container) return;

        var scrollLeft = container.scrollLeft;
        var scrollWidth = container.scrollWidth;
        var clientWidth = container.clientWidth;

        if (scrollLeft < LOAD_THRESHOLD) {
            expandLeft();
        } else if (scrollLeft + clientWidth > scrollWidth - LOAD_THRESHOLD) {
            expandRight();
        }
    }

    function expandLeft() {
        var oldStart = new Date(rangeStart);
        rangeStart = addDays(rangeStart, -14);
        loading = true;

        var url = apiUrl + "?from=" + formatDate(rangeStart) + "&to=" + formatDate(rangeEnd);
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var oldScrollWidth = container.scrollWidth;
                cachedTrailers = data.trailers;
                cachedReservations = data.reservations;
                renderGridNoScroll();
                var newScrollWidth = container.scrollWidth;
                container.scrollLeft += (newScrollWidth - oldScrollWidth);
                loading = false;
            })
            .catch(function () { loading = false; });
    }

    function expandRight() {
        rangeEnd = addDays(rangeEnd, 14);
        loading = true;

        var url = apiUrl + "?from=" + formatDate(rangeStart) + "&to=" + formatDate(rangeEnd);
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                cachedTrailers = data.trailers;
                cachedReservations = data.reservations;
                renderGridNoScroll();
                loading = false;
            })
            .catch(function () { loading = false; });
    }

    function renderGridNoScroll() {
        var days = getDays(rangeStart, rangeEnd);
        if (days.length === 0) return;

        var minWidth = 180 + (days.length * 52);
        var html = '<div class="calendar-grid" style="grid-template-columns: max-content repeat(' + days.length + ', minmax(48px, 1fr)); min-width:' + minWidth + 'px;">';

        html += '<div class="calendar-header-row">';
        html += '<div class="calendar-header-cell" style="text-align:left;">Anhänger</div>';

        for (var i = 0; i < days.length; i++) {
            var d = days[i];
            var isToday = d.getTime() === today.getTime();
            var isMonday = d.getDay() === 1;
            var cls = "calendar-header-cell";
            if (isToday) cls += " today";
            if (isMonday && i > 0) cls += " week-start";
            html += '<div class="' + cls + '">' + dayName(d) + '<br>' + formatDateShort(d) + '</div>';
        }
        html += '</div>';

        var resByTrailer = {};
        for (var r = 0; r < cachedReservations.length; r++) {
            var res = cachedReservations[r];
            if (!resByTrailer[res.trailer_id]) resByTrailer[res.trailer_id] = [];
            resByTrailer[res.trailer_id].push(res);
        }

        for (var t = 0; t < cachedTrailers.length; t++) {
            var trailer = cachedTrailers[t];
            html += '<div class="calendar-row">';
            html += '<div class="calendar-trailer-label">' + escapeHtml(trailer.name) + '</div>';

            var trailerRes = resByTrailer[trailer.id] || [];

            for (var di = 0; di < days.length; di++) {
                var day = days[di];
                var isToday2 = day.getTime() === today.getTime();
                var isWeekend2 = day.getDay() === 0 || day.getDay() === 6;
                var isMonday2 = day.getDay() === 1 && di > 0;
                var cellCls = "calendar-cell";
                if (isToday2) cellCls += " today";
                if (isWeekend2) cellCls += " weekend";
                if (isMonday2) cellCls += " week-start";

                html += '<div class="' + cellCls + '">';

                for (var ri = 0; ri < trailerRes.length; ri++) {
                    var rv = trailerRes[ri];
                    var rvStart = new Date(rv.start_date);
                    rvStart.setHours(0, 0, 0, 0);
                    var rvEnd = new Date(rv.end_date);
                    rvEnd.setHours(0, 0, 0, 0);

                    if (day >= rvStart && day < rvEnd) {
                        var isStart = day.getTime() === rvStart.getTime();
                        var visStart = rvStart < rangeStart ? rangeStart : rvStart;
                        var isVisStart = day.getTime() === visStart.getTime();

                        if (isVisStart) {
                            var endIdx = days.length;
                            for (var si = di; si < days.length; si++) {
                                if (days[si] >= rvEnd) { endIdx = si; break; }
                            }
                            var span = endIdx - di;
                            if (span < 1) span = 1;

                            var barCls = "calendar-bar";
                            barCls += rv.status === "pending" ? " calendar-bar--pending" : " calendar-bar--confirmed";
                            if (!isStart) barCls += " calendar-bar--clipped-start";

                            var rvEndMinusOne = addDays(rvEnd, -1);
                            var barLabel = escapeHtml(rv.customer_last_name);
                            var titleText = rv.customer_first_name + " " + rv.customer_last_name + " | " +
                                formatDateShort(rvStart) + " – " + formatDateShort(rvEndMinusOne);

                            var detailUrl = detailUrlTemplate.replace("__ID__", rv.id);
                            var barWidth = "calc(" + (span * 100) + "% + " + ((span - 1)) + "px)";

                            html += '<a href="' + detailUrl + '" class="' + barCls + '" title="' + escapeHtml(titleText) + '" style="width:' + barWidth + '; z-index:2;">' + barLabel + '</a>';
                        }
                    }
                }

                html += '</div>';
            }
            html += '</div>';
        }

        html += '</div>';
        calendarEl.innerHTML = html;
    }

    function escapeHtml(str) {
        if (!str) return "";
        return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }

    fetchAndRender();
});
