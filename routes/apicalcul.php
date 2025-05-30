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
            <th>Taxon</th>
            <th>% ID</th>
            <th>Incompatibilités</th>
            <th>T Index</th>
            <th>Qualité</th>
          </tr>
        </thead>
        <tbody>
          <template x-for="res in results" :key="res.taxon">
            <tr>
              <td x-text="res.taxon"></td>
              <td>
                <b><span x-text="res.percentId.toFixed(1) + '%'"></span></b>
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
              <td>
                <span x-text="res.tIndex.toFixed(2)"></span>
              </td>
              <td>
                <span x-text="res.quality"></span>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
    <div class="legend">
      <b>Légende :</b> %ID = pourcentage d'identification, T Index = indice de typicité, Qualité = fiabilité de l'identification
    </div>
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
        if (key === 'api10s') url = '/assets/db/api10s.json?v=1';
        else if (key === 'api20e') url = '/assets/db/api20e.json?v=12';
        else if (key === 'api20ne') url = '/assets/db/api20ne.json?v=12';
        else if (key === 'apistrep') url = '/assets/db/apistrep.json?v=14';
        else if (key === 'apicoryne') url = '/assets/db/apicoryne.json?v=15';
        else if (key === 'apinh') url = '/assets/db/apinh.json?v=14';
        else if (key === 'apistaph') url = '/assets/db/apistaph.json?v=14';
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
          } else if (key === 'api20ne') {
            this.dbError = "API 20NE : Bientôt disponible.";
          } else if (key === 'apistrep') {
            this.dbError = "API Strep : Bientôt disponible.";
          } else if (key === 'apicoryne') {
            this.dbError = "API Coryne : Bientôt disponible.";
          } else if (key === 'apinh') {
            this.dbError = "API NH : Bientôt disponible.";
          } else if (key === 'apistaph') {
            this.dbError = "API Staph : Bientôt disponible.";
          } else if (key === 'id32c') {
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

        // Constants for reading errors
        const ALPHA_POSITIVE = 0.001; // α+ = 10^-3
        const ALPHA_NEGATIVE = 0.01; // α- = 10^-2
        const S = 0.01; // S value for T index calculation (10^-2)

        // Calculate frequency of occurrence for each taxon
        const taxaResults = db.map(taxon => {
          let poFrequency = 1; // Frequency of occurrence for observed profile
          let ptFrequency = 1; // Frequency of most typical profile
          const incompatibilities = [];

          // Process each test in the profile
          for (const [test, expected] of Object.entries(profile)) {
            const percentPositive = taxon[test];
            if (percentPositive === undefined || expected === null) continue;

            // Calculate P+ and P-
            const pPositive = percentPositive / 100;
            const pNegative = 1 - pPositive;

            // Calculate frequency based on observed reaction
            let reactionFrequency;
            if (expected === "+") {
              // F+ = P+ (1 - α+) + (α+ × P-)
              reactionFrequency = pPositive * (1 - ALPHA_POSITIVE) + (ALPHA_POSITIVE * pNegative);
            } else {
              // F- = P- (1 - α-) + (α- × P+)
              reactionFrequency = pNegative * (1 - ALPHA_NEGATIVE) + (ALPHA_NEGATIVE * pPositive);
            }

            // Multiply to get overall frequency for observed profile
            poFrequency *= reactionFrequency;

            // Calculate most typical reaction frequency (+ when ≥50%, - when <50%)
            const typicalFrequency = (percentPositive >= 50) ?
              pPositive * (1 - ALPHA_POSITIVE) + (ALPHA_POSITIVE * pNegative) :
              pNegative * (1 - ALPHA_NEGATIVE) + (ALPHA_NEGATIVE * pPositive);

            // Multiply to get overall frequency for most typical profile
            ptFrequency *= typicalFrequency;

            // Check for tests against identification (frequency < 0.25)
            if (reactionFrequency < 0.25) {
              incompatibilities.push({
                test,
                expected,
                actual: percentPositive
              });
            }
          }

          return {
            taxon: taxon.Taxon,
            poFrequency,
            ptFrequency,
            modalFrequency: poFrequency / ptFrequency, // Fm = Po/Pt
            incompatibilites: incompatibilities,
            bacterium: taxon
          };
        });

        // Filter out taxa with extremely low frequencies
        const validTaxa = taxaResults.filter(t => t.poFrequency > 0.000001);

        // Calculate total frequency for normalization
        const totalFrequency = validTaxa.reduce((sum, t) => sum + t.poFrequency, 0) || 1;

        // Calculate %id and sort
        validTaxa.forEach(taxon => {
          // Calculate percentage of identification
          taxon.percentId = (taxon.poFrequency / totalFrequency) * 100;

          // Calculate T index
          // T = (log Fm - log S) / -log S
          taxon.tIndex = Math.max(0,
            (Math.log10(Math.max(taxon.modalFrequency, 0.000001)) - Math.log10(S)) / -Math.log10(S)
          );
        });

        // Sort by decreasing %id
        validTaxa.sort((a, b) => b.percentId - a.percentId);

        // Calculate ratios for first 4 taxa
        for (let i = 0; i < Math.min(validTaxa.length - 1, 4); i++) {
          validTaxa[i].ratio = validTaxa[i].percentId / (validTaxa[i + 1].percentId || 0.01);
        }

        // Find taxon with maximum ratio
        let maxRatio = 0;
        let maxRatioIndex = 0;
        for (let i = 0; i < Math.min(validTaxa.length - 1, 4); i++) {
          if (validTaxa[i].ratio > maxRatio) {
            maxRatio = validTaxa[i].ratio;
            maxRatioIndex = i;
          }
        }

        // Select taxa for identification (up to the maxRatioIndex)
        const selectedTaxa = validTaxa.slice(0, maxRatioIndex + 1);

        // Add quality assessment for each selected taxon
        selectedTaxa.forEach(taxon => {
          if (taxon.percentId >= 99.9 && taxon.tIndex >= 0.75) {
            taxon.quality = 'EXCELLENTE';
          } else if (taxon.percentId >= 99.0 && taxon.tIndex >= 0.50) {
            taxon.quality = 'TRÈS BONNE';
          } else if (taxon.percentId >= 90.0 && taxon.tIndex >= 0.25) {
            taxon.quality = 'BONNE';
          } else if (taxon.percentId >= 80.0 && taxon.tIndex >= 0) {
            taxon.quality = 'ACCEPTABLE';
          } else {
            taxon.quality = 'NON FIABLE';
          }
        });

        // Handle special cases
        const sumPercentId = selectedTaxa.reduce((sum, t) => sum + t.percentId, 0);

        // Check if multiple taxa from same genus - identification to genus level
        // This would require taxonomy data not available in this example

        // Check if profile is "doubtful" or "unacceptable"
        if (selectedTaxa.length === 0) {
          // Add a special result entry
          selectedTaxa.push({
            taxon: "PROFIL NON IDENTIFIABLE",
            percentId: 0,
            tIndex: 0,
            quality: "INACCEPTABLE",
            incompatibilites: []
          });
        } else if (sumPercentId < 80.0) {
          // Update quality for all taxa
          selectedTaxa.forEach(taxon => {
            taxon.quality = 'NON FIABLE';
          });
        }

        // Store selected taxa as results
        this.results = selectedTaxa.slice(0, 5); // Limit to top 5 taxa

        // Log analytics for this calculation
        const analyticsData = {
          db_type: this.selectedDb,
          profile: Object.fromEntries(
            Object.entries(this.profile).filter(([_, v]) => v !== null)
          ),
          results: this.results.map(r => ({
            taxon: r.taxon,
            percentId: r.percentId,
            tIndex: r.tIndex
          }))
        };

        if (window.logApiCalculation) {
          window.logApiCalculation(analyticsData);
        }
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
    background: #222;
    color: #eee;
  }

  .toggle-group button.active.white {
    background: #eee;
    color: #222;
  }

  .toggle-group button.active.orange {
    background: var(--orange);
    color: var(--bg);
  }

  .toggle-group button.active.purple {
    background: var(--purple);
    color: var(--bg);
  }


  .toggle-group button.active.pink {
    background: var(--pink);
    color: var(--bg);
  }

  .toggle-group button.active.brown {
    background: var(--brown);
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
</style>
