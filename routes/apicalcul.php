<div style="display: none;" href='/apicalcul' id="metadata">APIcalcul - iheb.tn</div>

<h1>🧪 APIcalcul</h1>

<div id="apicalcul-app" x-data="apicalculApp()" x-init="init()" class="apicalcul-container">
    <div class="notice">
        <b>Remarque : </b>Cette application n'a pas été validée scientifiquement. Ne pas l'utiliser dans un cadre pratique. À utiliser uniquement à des fins expérimentales.
    </div>
    <div class="apicalcul-db-select">
        <template x-for="dbOpt in dbOptions" :key="dbOpt.key">
            <button type="button"
                :class="{'active': selectedDb === dbOpt.key}"
                @click="selectDb(dbOpt.key)"
                x-text="dbOpt.label"></button>
        </template>
    </div>
    <template x-if="dbLoading">
        <div class="apicalcul-loading">Chargement de la base...</div>
    </template>
    <template x-if="dbError">
        <div class="apicalcul-error" x-text="dbError"></div>
    </template>
    <template x-if="!dbLoading && !dbError && criteria.length">
        <form @submit.prevent="calculate()" class="apicalcul-form">
            <div class="criteria-list">
                <template x-for="crit in criteria" :key="crit.key">
                    <div class="criterion" :class="`criterion-${crit.class}`">
                        <label :for="'crit-' + crit.key" class="criterion-label">
                            <span x-text="crit.label"></span>
                            <span class="criterion-abbr" x-text="crit.key"></span>
                        </label>
                        <div class="toggle-group">
                            <button type="button"
                                :class="{'active': profile[crit.key] === '+', [crit.posClass]: profile[crit.key] === '+'}"
                                @click="setProfile(crit.key, '+')">+</button>
                            <button type="button"
                                :class="{'active': profile[crit.key] === '-', [crit.negClass]: profile[crit.key] === '-'}"
                                @click="setProfile(crit.key, '-')">-</button>
                            <button type="button"
                                :class="{'active': profile[crit.key] === null, 'gray': profile[crit.key] === null}"
                                @click="setProfile(crit.key, null)">?</button>
                        </div>
                    </div>
                </template>
            </div>
            <div class="apicalcul-actions">
                <button type="submit" class="apicalcul-submit">Calculer</button>
                <button type="button" class="apicalcul-reset" @click="resetProfile()">Réinitialiser</button>
            </div>
        </form>
    </template>
    <template x-if="selectedDb === 'api20e' && !criteria.length && !dbLoading">
        <div class="apicalcul-error">API 20E : Bientôt disponible.</div>
    </template>
    <div class="apicalcul-results" x-show="results.length > 0">
        <h2>Résultats</h2>
        <div class="results-wrapper">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Bactérie</th>
                        <th>Probabilité</th>
                        <th>Typicité</th>
                        <th>Incompatibilités</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="res in results" :key="res.taxon">
                        <tr>
                            <td x-text="res.taxon"></td>
                            <td>
                                <b><span x-text="(res.probability * 100).toFixed(1) + '%'"></span></b>
                            </td>
                            <td>
                                <span x-text="(res.typicite * 100).toFixed(1) + '%'"></span>
                            </td>
                            <td>
                                <template x-if="res.incompatibilites.length > 0">
                                    <div class="incompatibilities">
                                        <template x-for="inc in res.incompatibilites" :key="inc.test">
                                            <div class="incompat-item">
                                                <span x-text="inc.test"></span>
                                                <span x-text="getTestValue(inc)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="res.incompatibilites.length === 0">
                                    <span>—</span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <!--<div class="legend">
            <b>Légende :</b> Probabilité = chance relative, Typicité = adéquation au profil, Incompatibilité = tests incompatibles.
        </div>-->
    </div>
</div>

<script>
    function apicalculApp() {
        return {
            dbOptions: [{
                    key: 'api10s',
                    label: 'API 10S'
                },
                {
                    key: 'api20e',
                    label: 'API 20E'
                },
                {
                    key: 'api20ne',
                    label: 'API 20NE'
                },
                {
                    key: 'apistrep',
                    label: 'API Strep'
                },
                {
                    key: 'apicoryne',
                    label: 'API Coryne'
                },
                {
                    key: 'apinh',
                    label: 'API NH'
                },
                {
                    key: 'apistaph',
                    label: 'API Staph'
                },
                {
                    key: 'id32c',
                    label: 'ID32 C'
                },
            ],
            selectedDb: 'api10s',
            dbLoading: false,
            dbError: null,
            db: [],
            criteria: [],
            profile: {},
            results: [],
            // --- Load DB and criteria ---
            async init() {
                const savedDb = localStorage.getItem('apicalcul-selected-db');
                if (savedDb) {
                    this.selectedDb = savedDb;
                }
                await this.loadDb(this.selectedDb);
            },
            async selectDb(key) {
                if (this.selectedDb === key) return;
                this.selectedDb = key;
                localStorage.setItem('apicalcul-selected-db', key);
                this.results = [];
                await this.loadDb(key);
            },
            async loadDb(key) {
                this.dbLoading = true;
                this.dbError = null;
                this.criteria = [];
                this.db = [];
                this.profile = {};
                let url = '';
                if (key === 'api10s') url = '/assets/db/api10s.json';
                else if (key === 'api20e') url = '/assets/db/api20e.json?v=7';
                else if (key === 'api20ne') url = '/assets/db/api20ne.json?v=8';
                else if (key === 'apistrep') url = '/assets/db/apistrep.json?v=10';
                else if (key === 'apicoryne') url = '/assets/db/apicoryne.json?v=15';
                else if (key === 'apinh') url = '/assets/db/apinh.json?v=11';
                else if (key === 'apistaph') url = '/assets/db/apistaph.json?v=12';
                else if (key === 'id32c') url = '/assets/db/id32c.json?v=10';
                else {
                    this.dbError = 'Base inconnue.';
                    this.dbLoading = false;
                    return;
                }
                try {
                    const resp = await fetch(url);
                    if (!resp.ok) throw new Error('Erreur de chargement');
                    const data = await resp.json();
                    this.db = data.db;
                    this.criteria = data.criteria;

                    // Load saved profile for this specific database
                    const savedProfile = localStorage.getItem(`apicalcul-profile-${key}`);
                    if (savedProfile) {
                        const parsed = JSON.parse(savedProfile);
                        this.criteria.forEach(crit => {
                            this.profile[crit.key] = parsed[crit.key] ?? null;
                        });
                    } else {
                        // Initialize empty profile
                        this.criteria.forEach(crit => {
                            this.profile[crit.key] = null;
                        });
                    }
                } catch (e) {
                    if (key === 'api20e') {
                        this.dbError = "API 20E : Bientôt disponible.";
                    } else if (key === 'api20ne'){
                        this.dbError = "API 20NE : Bientôt disponible.";
                    } else if (key === 'apistrep'){
                        this.dbError = "API Strep : Bientôt disponible.";
                    } else if (key === 'apicoryne'){
                        this.dbError = "API Coryne : Bientôt disponible.";
                    } else if (key === 'apinh'){
                        this.dbError = "API NH : Bientôt disponible.";
                    } else if (key === 'apistaph'){
                        this.dbError = "API Staph : Bientôt disponible.";
                    } else if (key === 'id32c'){
                        this.dbError = "ID32 C  : Bientôt disponible.";
                    } else {
                        this.dbError = "Erreur de chargement de la base.";
                    }
                }
                this.dbLoading = false;
            },
            setProfile(key, val) {
                this.profile[key] = val;
                localStorage.setItem(`apicalcul-profile-${this.selectedDb}`, JSON.stringify(this.profile));
            },
            resetProfile() {
                this.profile = {};
                for (const crit of this.criteria) {
                    this.profile[crit.key] = null;
                }
                localStorage.removeItem(`apicalcul-profile-${this.selectedDb}`);
                this.results = [];
            },
            getTestClass(inc) {
                const crit = this.criteria.find(c => c.key === inc.test);
                if (!crit) return 'gray';
                return inc.expected === '+' ? crit.posClass : crit.negClass;
            },
            getTestValue(inc) {
                return `${inc.expected} (${inc.actual}%)`;
            },
            calculate() {
                const profile = this.profile;
                const db = this.db;
                const probs = db.map(bacterium => {
                    let product = 1;
                    let pTypicite = 1;
                    for (const [test, expected] of Object.entries(profile)) {
                        const value = bacterium[test];
                        if (value === undefined || expected === null) continue;
                        if (value < 50) {
                            pTypicite *= 100 - value;
                        } else {
                            pTypicite *= value;
                        }
                        if (expected === "+") {
                            product *= value === 0 ? 1 : value;
                        } else if (expected === "-") {
                            product *= value === 100 ? 1 : (100 - value);
                        } else {
                            product *= 1;
                        }
                    }
                    return {
                        taxon: bacterium.Taxon,
                        raw: product,
                        pTypicite: pTypicite,
                        bacterium
                    };
                });
                const total = probs.reduce((sum, p) => sum + p.raw, 0) || 1;
                this.results = probs.map(p => {
                        const incompatibilites = [];
                        for (const [test, expected] of Object.entries(profile)) {
                            const value = p.bacterium[test];
                            if (value === undefined || expected === null) continue;
                            if ((value < 25 && expected === "+") || (value > 75 && expected === "-")) {
                                incompatibilites.push({
                                    test,
                                    expected,
                                    actual: value
                                });
                            }
                        }
                        return {
                            taxon: p.taxon,
                            probability: (p.raw / total) || 0,
                            typicite: (
                                (Math.log((p.raw || 0.000001) / (p.pTypicite || 0.000001)) - Math.log(0.000001)) / -Math.log(0.000001)
                            ),
                            incompatibilites
                        };
                    })
                    .filter(item => item.probability > 0.01) // Filter for probabilities over 1%
                    .sort((a, b) => b.probability - a.probability) // Sort in descending order
                    .slice(0, 5); // Take the top 5 results
            }
        };
    };
</script>

<style>
    h1 {
        text-align: center;
        margin-bottom: 1rem;
    }

    h2 {
        margin-bottom: 1rem;
        color: var(--orange)
    }

    .apicalcul-container {
        background: var(--bg1);
        border-radius: 8px;
        padding: 1.5rem 1rem;
        width: calc(100% - 2rem);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .apicalcul-db-select {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.2rem;
    }

    .apicalcul-db-select button {
        border: none;
        border-radius: 4px;
        padding: 0.3em 1em;
        font-size: 1em;
        font-weight: bold;
        background: var(--bg3);
        color: var(--fg2);
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
        outline: none;
    }

    .apicalcul-db-select button.active {
        background: var(--green);
        color: var(--bg);
    }

    .apicalcul-db-select button:not(.active):hover {
        background: var(--green-dim);
        color: var(--bg);
    }

    .apicalcul-loading {
        text-align: center;
        color: var(--blue);
        font-weight: bold;
        margin-bottom: 1rem;
    }

    .apicalcul-error {
        text-align: center;
        color: var(--red);
        font-weight: bold;
        margin-bottom: 1rem;
    }

    .apicalcul-form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .criteria-list {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: center;
    }

    .criterion {
        background: var(--bg2);
        border-radius: 6px;
        padding: 0.7rem 1rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .criterion-label {
        font-weight: bold;
        color: var(--fg2);
        margin-bottom: 0.3rem;
        text-align: center;
        font-size: 1rem;
    }

    .criterion-abbr {
        font-size: 0.85em;
        color: var(--gray);
        margin-left: 0.2em;
    }

    .toggle-group {
        display: flex;
        gap: 0.3rem;
    }

    .toggle-group button {
        border: none;
        border-radius: 4px;
        padding: 0.25em 0.7em;
        font-size: 1.1em;
        font-weight: bold;
        background: var(--bg3);
        color: var(--fg2);
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
        outline: none;
    }

    .toggle-group button.active {
        background: var(--green);
        color: var(--bg);
    }

    .toggle-group button.active.yellow {
        background: var(--yellow);
        color: var(--bg);
    }

    .toggle-group button.active.green {
        background: var(--green);
        color: var(--bg);
    }

    .toggle-group button.active.red {
        background: var(--red);
        color: var(--bg);
    }

    .toggle-group button.active.blue {
        background: var(--blue);
        color: var(--bg);
    }

    .toggle-group button.active.aqua {
        background: var(--aqua);
        color: var(--bg);
    }

    .toggle-group button.active.gray {
        background: var(--gray);
        color: var(--bg);
    }

    .toggle-group button.active.black {
        background: var(--fg);
        color: var(--bg);
    }

    .toggle-group button.active.white {
        background: var(--bg_h);
        color: var(--fg);
    }

    .toggle-group button.active.orange {
        background: var(--orange);
        color: var(--bg);
    }

    .toggle-group button.active.purple {
        background: var(--purple);
        color: var(--bg);
    }

    .toggle-group button:not(.active):hover {
        background: var(--bg4);
        color: var(--fg);
    }

    .apicalcul-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 0.5rem;
    }

    .apicalcul-submit,
    .apicalcul-reset {
        padding: 0.5em 1.2em;
        border-radius: 4px;
        border: none;
        font-weight: bold;
        font-size: 1em;
        cursor: pointer;
        background: var(--blue);
        color: var(--bg);
        transition: background 0.15s;
    }

    .apicalcul-reset {
        background: var(--red);
    }

    .apicalcul-submit:hover {
        background: var(--blue-dim);
    }

    .apicalcul-reset:hover {
        background: var(--red-dim);
    }

    .apicalcul-results {
        margin-top: 1rem;
        background: var(--bg2);
        border-radius: 6px;
        padding: 1rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    .results-wrapper {
        overflow-x: auto;
    }

    .results-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0.7rem;
    }

    .results-table th,
    .results-table td {
        padding: 0.5em 0.6em;
        border-bottom: 1px solid var(--bg3);
        text-align: center;
    }

    .results-table th {
        color: var(--purple);
        font-weight: bold;
        font-size: 1em;
        background: var(--bg3);
    }

    .results-table td {
        color: var(--fg2);
        font-size: 1em;
    }

    .results-table tr {
        background: var(--bg2);
        border-right: 1px solid var(--bg3);
        border-left: 1px solid var(--bg3);
    }

    .incompatibilities {
        display: flex;
        flex-wrap: wrap;
        gap: 0.3rem;
        justify-content: center;
    }

    .incompat-item {
        font-size: 0.9em;
        padding: 0.2em 0.4em;
        border-radius: 3px;
        background: var(--bg3);
    }

    .incompat-item span:first-child {
        margin-right: 0.3em;
    }

    .incompat-count {
        color: var(--red);
        font-weight: bold;
    }

    .legend {
        font-size: 0.9em;
        color: var(--fg4);
        margin-top: 0.5em;
        text-align: left;
    }

    .notice {
        font-size: 0.9em;
        color: var(--fg4);
        margin-bottom: 1rem;
        text-align: center;
    }

    /* Test result colors */
    .yellow {
        color: var(--yellow);
        font-weight: bold;
    }

    .green {
        color: var(--green);
        font-weight: bold;
    }

    .red {
        color: var(--red);
        font-weight: bold;
    }

    .blue {
        color: var(--blue);
        font-weight: bold;
    }

    .aqua {
        color: var(--aqua);
        font-weight: bold;
    }

    .gray {
        color: var(--gray);
        font-weight: bold;
    }

    .black {
        color: var(--fg2);
        font-weight: bold;
    }

    .white {
        color: var(--bg4);
        font-weight: bold;
    }

    .orange {
        color: var(--orange);
        font-weight: bold;
    }

    .purple {
        color: var(--purple);
        font-weight: bold;
    }
</style>
