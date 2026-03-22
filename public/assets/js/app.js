'use strict';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    if (! Alpine) {
        return;
    }

    Alpine.data('additionalItemForm', () => ({
        inputType: 'text',
        inputTypeEl: null,
        inputTypeChangeHandler: null,
        init() {
            const el = document.getElementById('input_type');
            if (!el) {
                return;
            }

            this.inputTypeEl = el;
            this.inputType = el.value;
            this.inputTypeChangeHandler = () => {
                this.inputType = el.value;
            };
            el.addEventListener('change', this.inputTypeChangeHandler);
        },
        destroy() {
            if (this.inputTypeEl === null || this.inputTypeChangeHandler === null) {
                return;
            }

            this.inputTypeEl.removeEventListener('change', this.inputTypeChangeHandler);
            this.inputTypeEl = null;
            this.inputTypeChangeHandler = null;
        },
    }));

    Alpine.data('submitState', () => ({
        submitting: false,
        pageShowHandler: null,
        init() {
            this.pageShowHandler = (event) => {
                if (event.persisted) {
                    this.submitting = false;
                }
            };

            window.addEventListener('pageshow', this.pageShowHandler);
        },
        destroy() {
            if (this.pageShowHandler === null) {
                return;
            }

            window.removeEventListener('pageshow', this.pageShowHandler);
            this.pageShowHandler = null;
        },
        startSubmitting(event) {
            if (this.submitting) {
                event.preventDefault();

                return;
            }

            this.submitting = true;
        },
    }));

    /**
     * 開催枠カレンダー（月グリッド＋日別一覧）。サーバーが組み立てた payload を受け取り、選択日の枠だけを表示する。
     *
     * @param {object} payload
     * @param {number} payload.year
     * @param {number} payload.month
     * @param {string} payload.monthLabel
     * @param {string[]} payload.weekdayLabels
     * @param {Array<Array<{ ymd: string|null, day: number|null, inMonth: boolean, symbol: string|null, isToday: boolean, hasSessions: boolean }>>} payload.weeks
     * @param {Record<string, Array<object>>} payload.sessionsByDay
     */
    Alpine.data('sessionCalendar', (payload) => ({
        year: payload.year,
        month: payload.month,
        monthLabel: payload.monthLabel,
        weekdayLabels: payload.weekdayLabels,
        weeks: payload.weeks,
        sessionsByDay: payload.sessionsByDay,
        selectedYmd: null,
        selectDay(cell) {
            if (!cell || !cell.inMonth || !cell.ymd) {
                return;
            }
            this.selectedYmd = this.selectedYmd === cell.ymd ? null : cell.ymd;
        },
        get selectedSessions() {
            if (!this.selectedYmd) {
                return [];
            }

            return this.sessionsByDay[this.selectedYmd] || [];
        },
    }));
});
