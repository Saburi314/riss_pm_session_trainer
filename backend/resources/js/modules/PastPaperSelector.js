export class PastPaperSelector {
    constructor(config) {
        this.selectYear = config.selectYear;
        this.selectSeason = config.selectSeason;
        this.selectPeriod = config.selectPeriod;
        this.pastPapers = config.pastPapers;
        this.seasonOrder = { 'spring': 1, 'autumn': 2, 'special': 3 };
        this.periodOrder = { 'pm1': 1, 'pm2': 2, 'pm': 3 };

        if (this.selectYear) {
            this.init();
        }
    }

    init() {
        const years = [...new Set(this.pastPapers.map(p => p.year))].sort((a, b) => b - a);
        years.forEach(y => {
            const paper = this.pastPapers.find(p => p.year === y);
            const opt = document.createElement('option');
            opt.value = y;
            opt.textContent = `${y}年 (${paper.gengo})`;
            this.selectYear.appendChild(opt);
        });

        this.selectYear.addEventListener('change', () => this.handleYearChange());
        this.selectSeason.addEventListener('change', () => this.handleSeasonChange());
    }

    handleYearChange() {
        const yearVal = parseInt(this.selectYear.value);
        const filtered = this.pastPapers.filter(p => p.year === yearVal);

        const seasons = [...new Set(filtered.map(p => p.season))].sort((a, b) => {
            return (this.seasonOrder[a] || 99) - (this.seasonOrder[b] || 99);
        });

        this.selectSeason.innerHTML = '<option value="" selected disabled>時期を選択</option>';
        seasons.forEach(s => {
            const paper = filtered.find(p => p.season === s);
            const opt = document.createElement('option');
            opt.value = s;
            opt.textContent = paper.season_name;
            this.selectSeason.appendChild(opt);
        });
        this.selectSeason.disabled = false;
        this.selectPeriod.disabled = true;
        this.selectPeriod.innerHTML = '<option value="" selected disabled>区分を選択</option>';
    }

    handleSeasonChange() {
        const yearVal = parseInt(this.selectYear.value);
        const seasonVal = this.selectSeason.value;
        let filtered = this.pastPapers.filter(p => p.year === yearVal && p.season === seasonVal);

        filtered.sort((a, b) => {
            return (this.periodOrder[a.period] || 99) - (this.periodOrder[b.period] || 99);
        });

        const seenPeriods = new Set();
        this.selectPeriod.innerHTML = '<option value="" selected disabled>区分を選択</option>';
        filtered.forEach(p => {
            const key = `${p.period}`;
            if (seenPeriods.has(key)) return;
            seenPeriods.add(key);

            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.period_name;
            this.selectPeriod.appendChild(opt);
        });
        this.selectPeriod.disabled = false;
    }

    /**
     * URLパラメータなどから値を外部セットする場合に使用
     */
    setValue(year, season, periodId) {
        if (year) {
            this.selectYear.value = year;
            this.handleYearChange();
        }
        if (season) {
            this.selectSeason.value = season;
            this.handleSeasonChange();
        }
        if (periodId) {
            this.selectPeriod.value = periodId;
        }
    }
}
