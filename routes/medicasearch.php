<div style="display: none;" href='/medicasearch' id="metadata">Médicasearch - iheb.tn</div>

<!-- TODO: Implement medicasearch analytics-->
<h1>💊 Médicasearch</h1>

<!-- Search Component Container -->
<div id="medicament-search" x-data="medicamentSearchApp()" x-init="initLoader()">

  <!-- Loading Database Indicator -->
  <div x-show="isLoading" class="loading-section">
    <p x-text="loadingStatus" class="loading-status-text"></p>
    <!-- Progress bar for determinate loading states -->
    <progress
      x-show="loadingProgress > 0 && loadingProgress < 100"
      x-bind:value="loadingProgress"
      max="100"
      class="loading-progress-bar"
      aria-label="Progression du chargement de la base de données"
    ></progress>
    <!-- Unicode spinners for indeterminate states -->
    <span x-show="isLoading && loadingProgress >= 100" class="loading-spinner" aria-hidden="true">⚙️</span>
    <span x-show="isLoading && loadingProgress === 0" class="loading-spinner" aria-hidden="true">⏳</span>
  </div>

  <!-- Search UI (Shown after loading) -->
  <div x-show="dbLoaded" class="search-ui-section" x-transition>
    <input
      type="search"
      id="search-term-input"
      placeholder="Nom, DCI, Indication..."
      x-model="searchTerm"
      @keydown.enter="performSearch()"
      @input="searchPerformed = false; searchResults = []"
      class="search-input"
      aria-label="Terme de recherche"
    >
    <button
      @click="performSearch()"
      class="search-button"
      :disabled="isLoading || !searchTerm.trim()"
      aria-label="Effectuer la recherche"
    >Recherche</button>
    <!--<p x-show="searchResults.length > 0" x-text="resultCountText" class="result-count-text" aria-live="polite"></p>-->
  </div>

  <!-- Search Results Section -->
  <div id="search-results-container" class="results-section" aria-live="polite">
    <!-- Loading Results Indicator (New) -->
    <div x-show="searchPerformed && !searchError && !isLoading && searchInProgress" class="results-loading-indicator">
      <span class="rotating-icon" aria-hidden="true">🔍</span>
      <span>Recherche en cours...</span>
    </div>

    <!-- Filter Buttons Area -->
    <div x-show="searchResults.length > 0" class="filter-controls">
      <button @click="activeFilter = 'all'"
        :class="{ 'active-filter-button': activeFilter === 'all' }"
        class="filter-button">
        Tous
      </button>
      <button @click="activeFilter = 'hasPrice'"
        :class="{ 'active-filter-button': activeFilter === 'hasPrice' }"
        class="filter-button">
        Avec Prix
      </button>
      <button @click="activeFilter = 'hasLinks'"
        :class="{ 'active-filter-button': activeFilter === 'hasLinks' }"
        class="filter-button">
        Avec Liens
      </button>
      <!--<span class="filter-count"
        x-text="`(${filteredResults.length} affiché${filteredResults.length !== 1 ? 's' : ''})`"
        aria-live="polite">
      </span>-->
    </div>

    <!-- Error Message -->
    <p x-show="searchError" x-text="searchError" class="search-error-message"></p>

    <!-- No Results Message -->
    <p x-show="!isLoading && searchPerformed && searchResults.length === 0 && !searchError && !searchInProgress" class="no-results-message">
      Aucun résultat trouvé pour "<span x-text="lastSearchedTerm"></span>".
    </p>

    <!-- Results list section - only displaying sections when they have content -->
    <template x-for="result in filteredResults" :key="result.rowid">
      <div class="result-item" x-data="{ open: false }">
        <!-- Result Header (Always Visible) -->
        <div @click="open = !open" class="result-header">
          <div class="result-title">
            <span class="prop-value prop-value-nom-commercial" x-html="makeTitle(result.Nom_Commercial,result.Forme,result.Presentation,result.PRIX_PUBLIC)"></span>
            <template x-if="result.Dosage">
              <span class="prop-value prop-value-dosage" x-text="` (${result.Dosage})`"></span>
            </template>
          </div>
          <!-- Rotating Arrow Indicator -->
          <span class="arrow-indicator"
            :class="{ 'rotate-90': open }"
            aria-hidden="true">
            ❯
          </span>
        </div>

        <!-- Collapsible Content -->
        <div x-show="open" x-collapse class="result-details"
             x-init="$nextTick(() => updateLastVisibleSection($el))"
             @x-collapse:expanded="updateLastVisibleSection($el)">

          <!-- Primary Information -->
          <div class="details-section"
               x-show="result.DCI || result.Laboratoire"
               :class="{'last-visible-section': isLastVisibleSection($el)}"
               x-effect="if(open) updateVisibilityStatus($el, result.DCI || result.Laboratoire)">
            <template x-if="result.DCI">
              <div class="field-container field-dci">
                <strong class="prop-label prop-label-dci">DCI:</strong>
                <span class="prop-value prop-value-dci" x-text="result.DCI"></span>
              </div>
            </template>
            <template x-if="result.Laboratoire">
              <div class="field-container field-laboratoire">
                <strong class="prop-label prop-label-laboratoire">Laboratoire:</strong>
                <span class="prop-value prop-value-laboratoire" x-text="result.Laboratoire"></span>
              </div>
            </template>
          </div>

          <!-- Pricing & CNAM Info -->
          <div class="details-section"
               x-show="result.PRIX_PUBLIC || result.TARIF_REFERENCE || result.CATEGORIE || result.AP || result.CODE_PCT"
               :class="{'last-visible-section': isLastVisibleSection($el)}"
               x-effect="if(open) updateVisibilityStatus($el, result.PRIX_PUBLIC || result.TARIF_REFERENCE || result.CATEGORIE || result.AP || result.CODE_PCT)">
            <template x-if="result.PRIX_PUBLIC">
              <div class="field-container field-prix-public">
                <strong class="prop-label prop-label-prix-public">Prix Public:</strong>
                <span class="prop-value prop-value-prix-public" x-text="result.PRIX_PUBLIC_formatted + ' TND'"></span>
              </div>
            </template>
            <template x-if="result.TARIF_REFERENCE">
              <div class="field-container field-tarif-reference">
                <strong class="prop-label prop-label-tarif-reference">Remboursement CNAM:</strong>
                <span class="prop-value prop-value-tarif-reference" x-text="result.TARIF_REFERENCE_formatted + ' TND'"></span>
              </div>
            </template>
            <!-- Merged VEIC and Categorie fields -->
            <template x-if="result.VEIC || result.CATEGORIE">
              <div class="field-container field-veic">
                <strong class="prop-label prop-label-veic">VEIC:</strong>
                <span class="prop-value prop-value-veic" x-text="expandVEIC(result.VEIC || formatCategorie(result.CATEGORIE))"></span>
              </div>
            </template>
            <template x-if="result.AP">
              <div class="field-container field-ap">
                <strong class="prop-label prop-label-ap">AP:</strong>
                <span class="prop-value prop-value-ap" x-text="result.AP"></span>
              </div>
            </template>
            <template x-if="result.CODE_PCT">
              <div class="field-container field-code-pct">
                <strong class="prop-label prop-label-code-pct">Code PCT:</strong>
                <span class="prop-value prop-value-code-pct" x-text="result.CODE_PCT"></span>
              </div>
            </template>
          </div>

          <!-- Regulatory Details -->
          <div class="details-section"
               x-show="result.AMM || result.Date_AMM || result.Tableau"
               :class="{'last-visible-section': isLastVisibleSection($el)}"
               x-effect="if(open) updateVisibilityStatus($el, result.AMM || result.Date_AMM || result.Tableau)">
            <template x-if="result.AMM">
              <div class="field-container field-amm">
                <strong class="prop-label prop-label-amm">AMM:</strong>
                <span class="prop-value prop-value-amm" x-text="result.AMM"></span>
              </div>
            </template>
            <template x-if="result.Date_AMM">
              <div class="field-container field-date-amm">
                <strong class="prop-label prop-label-date-amm">Date AMM:</strong>
                <span class="prop-value prop-value-date-amm" x-text="result.Date_AMM"></span>
              </div>
            </template>
            <template x-if="result.Tableau">
              <div class="field-container field-tableau">
                <strong class="prop-label prop-label-tableau">Tableau:</strong>
                <span class="prop-value prop-value-tableau" x-text="result.Tableau"></span>
              </div>
            </template>
          </div>

          <!-- Formulation & Presentation -->
          <div class="details-section"
               x-show="result.Forme || result.Presentation || result.Duree_Conservation || result.Conditionnement_Primaire || result.Specification_Conditionnement"
               :class="{'last-visible-section': isLastVisibleSection($el)}"
               x-effect="if(open) updateVisibilityStatus($el, result.Forme || result.Presentation || result.Duree_Conservation || result.Conditionnement_Primaire || result.Specification_Conditionnement)">
            <template x-if="result.Forme">
              <div class="field-container field-forme">
                <strong class="prop-label prop-label-forme">Forme:</strong>
                <span class="prop-value prop-value-forme" x-text="result.Forme"></span>
              </div>
            </template>
            <template x-if="result.Presentation">
              <div class="field-container field-presentation">
                <strong class="prop-label prop-label-presentation">Présentation:</strong>
                <span class="prop-value prop-value-presentation" x-text="result.Presentation"></span>
              </div>
            </template>
            <template x-if="result.Duree_Conservation">
              <div class="field-container field-duree-conservation">
                <strong class="prop-label prop-label-duree-conservation">Durée Conserv.:</strong>
                <span class="prop-value prop-value-duree-conservation" x-text="result.Duree_Conservation"></span>
              </div>
            </template>
            <template x-if="result.Conditionnement_Primaire">
              <div class="field-container field-conditionnement-primaire">
                <strong class="prop-label prop-label-conditionnement-primaire">Condit. Primaire:</strong>
                <span class="prop-value prop-value-conditionnement-primaire" x-text="result.Conditionnement_Primaire"></span>
              </div>
            </template>
            <template x-if="result.Specification_Conditionnement">
              <div class="field-container field-specification-conditionnement">
                <strong class="prop-label prop-label-specification-conditionnement">Spécif. Condit.:</strong>
                <span class="prop-value prop-value-specification-conditionnement" x-text="result.Specification_Conditionnement"></span>
              </div>
            </template>
          </div>

          <!-- Classification -->
          <div class="details-section"
               x-show="result.Classe_Therapeutique || result.Sous_Classe_Therapeutique"
               :class="{'last-visible-section': isLastVisibleSection($el)}"
               x-effect="if(open) updateVisibilityStatus($el, result.Classe_Therapeutique || result.Sous_Classe_Therapeutique)">
            <template x-if="result.Classe_Therapeutique">
              <div class="field-container field-classe-therapeutique">
                <strong class="prop-label prop-label-classe-therapeutique">Classe Thérapeutique:</strong>
                <span class="prop-value prop-value-classe-therapeutique" x-text="result.Classe_Therapeutique"></span>
              </div>
            </template>
            <template x-if="result.Sous_Classe_Therapeutique">
              <div class="field-container field-sous-classe-therapeutique">
                <strong class="prop-label prop-label-sous-classe-therapeutique">Sous-Classe:</strong>
                <span class="prop-value prop-value-sous-classe-therapeutique" x-text="result.Sous_Classe_Therapeutique"></span>
              </div>
            </template>
          </div>

          <!-- Indications -->
          <template x-if="result.Indications">
            <div class="details-section field-indications"
                 :class="{'last-visible-section': isLastVisibleSection($el)}"
                 x-effect="if(open) updateVisibilityStatus($el, true)">
              <strong class="prop-label prop-label-indications">Indications:</strong>
              <span class="prop-value prop-value-indications" x-text="result.Indications"></span>
            </div>
          </template>

          <!-- Links -->
          <div class="details-section field-links"
               x-show="result.RCP_Link || result.Notice_Link"
               :class="{'last-visible-section': isLastVisibleSection($el)}"
               x-effect="if(open) updateVisibilityStatus($el, result.RCP_Link || result.Notice_Link)">
            <strong class="prop-label prop-label-links">Liens:</strong>
            <template x-if="result.RCP_Link">
              <a class="prop-value prop-value-rcp-link" :href="result.RCP_Link" target="_blank" rel="noopener noreferrer">RCP</a>
            </template>
            <template x-if="result.Notice_Link">
              <a class="prop-value prop-value-notice-link" :href="result.Notice_Link" target="_blank" rel="noopener noreferrer" style="margin-left: 5px;">Notice</a>
            </template>
          </div>
        </div>
        <!-- End Collapsible Content -->
      </div>
    </template>
  </div>
</div> <!-- End Search Component Container -->

<script>
function makeTitle(Nom_Commercial, Forme, Presentation, PRIX_PUBLIC) {
  if (Forme !== null) {
    Forme = ' ' + Forme;
  } else {
    Forme = '';
  }

  if (Presentation !== null) {
    Presentation = ' ' + Presentation;
  } else {
    Presentation = '';
  }

  return '<span class="title-main">' + Nom_Commercial + '</span><span class="title-main">' + Forme + Presentation + '</span>';
}

  function medicamentSearchApp() {
    return {
    isLoading: true,
    loadingStatus: 'Initialisation...',
    loadingProgress: 0,
    dbLoaded: false,
    db: null,
    SQL: null,
    searchTerm: '',
    lastSearchedTerm: '',
    searchResults: [],
    searchError: null,
    resultCountText: '',
    searchPerformed: false,
    searchInProgress: false,
    activeFilter: 'all',
    dbPath: '/assets/db/medicaments_fts.db',
    ftsTableName: 'medicaments_fts',

    isLastVisibleSection(el) {
      if (!el || !el.parentElement) return false;
      const sections = Array.from(el.parentElement.querySelectorAll('.details-section'));
      const visibleSections = sections.filter(section =>
        section.offsetParent !== null
      );
      return visibleSections.length > 0 && visibleSections[visibleSections.length - 1] === el;
    },

    updateVisibilityStatus(el, isVisible) {
      this.$nextTick(() => {
        if (el && el.parentElement) {
          this.updateLastVisibleSection(el.parentElement);
        }
      });
    },

    updateLastVisibleSection(container) {
      if (!container) return;
      setTimeout(() => {
        const sections = Array.from(container.querySelectorAll('.details-section'));
        sections.forEach(section => section.classList.remove('last-visible-section'));
        const visibleSections = sections.filter(section =>
          section.offsetParent !== null
        );
        if (visibleSections.length > 0) {
          visibleSections[visibleSections.length - 1].classList.add('last-visible-section');
        }
      }, 50);
    },

    formatCategorie(categorie) {
      if (!categorie) return '';
      if (categorie === 'Vital') return 'V';
      if (categorie === 'Essentiel') return 'E';
      if (categorie === 'Intermédiaire') return 'I';
      if (categorie === 'Confort') return 'C';
      return categorie;
    },

    expandVEIC(value) {
      if (!value) return '';
      if (value === 'V') return 'Vital';
      if (value === 'E') return 'Essentiel';
      if (value === 'I') return 'Intermédiaire';
      if (value === 'C') return 'Confort';
      return value;
    },

    get filteredResults() {
      let results = this.searchResults;

      if (this.activeFilter === 'hasPrice') {
        results = results.filter(result => result.PRIX_PUBLIC != null && result.PRIX_PUBLIC !== '');
      } else if (this.activeFilter === 'hasLinks') {
        results = results.filter(result => (result.RCP_Link && result.RCP_Link.trim() !== '') || (result.Notice_Link && result.Notice_Link.trim() !== ''));
      }

      return results.slice(0, 100);
    },

    async initLoader() {
      this.isLoading = true;
      this.dbLoaded = false;
      this.searchError = null;
      this.loadingProgress = 0;

      try {
        this.updateStatus('Initialisation...', 5);
        this.SQL = await initSqlJs({
          locateFile: filename => `/${filename}`
        });
        this.updateStatus(`Demande du fichier de base de données...`, 15);

        const response = await fetch(this.dbPath);
        if (!response.ok) {
          throw new Error(`Échec de la récupération de la base de données: ${response.statusText} (Statut: ${response.status})`);
        }

        const contentLength = response.headers.get('Content-Length');
        const totalSize = contentLength ? parseInt(contentLength, 10) : null;

        if (!totalSize) {
          this.updateStatus('Taille de la base de données inconnue. Chargement...', 20);
          this.loadingProgress = 0;
        } else {
          this.updateStatus(`Téléchargement de la base de données (${(totalSize / 1024 / 1024).toFixed(2)} Mo)...`, 20);
        }

        if (!response.body) {
          throw new Error('ReadableStream non supporté ou la réponse fetch manque de corps.');
        }

        const reader = response.body.getReader();
        let bytesLoaded = 0;
        let chunks = [];

        while (true) {
          const { done, value } = await reader.read();
          if (done) break;

          chunks.push(value);
          bytesLoaded += value.length;

          if (totalSize) {
            const progress = (bytesLoaded / totalSize) * 100;
            this.loadingProgress = Math.min(95, 20 + (progress * 0.75));
            this.updateStatus(`Téléchargement de la base de données pour la fonctionnalité hors ligne... ${(bytesLoaded / 1024 / 1024).toFixed(2)} Mo / ${(totalSize / 1024 / 1024).toFixed(2)} Mo`);
          } else {
            this.updateStatus(`Chargement de la base de données... ${(bytesLoaded / 1024 / 1024).toFixed(2)} Mo chargés`);
          }
        }
        this.updateStatus('Téléchargement terminé. Traitement...', 95);

        const finalTotalSize = totalSize || bytesLoaded;
        let dbArray = new Uint8Array(finalTotalSize);
        let offset = 0;
        for (const chunk of chunks) {
          dbArray.set(chunk, offset);
          offset += chunk.length;
        }
        chunks = [];

        this.updateStatus('Chargement de la base de données en mémoire...', 98);
        this.db = new this.SQL.Database(dbArray);
        this.updateStatus('Base de données prête !', 100);
        this.dbLoaded = true;
        this.isLoading = false;

      } catch (err) {
        this.updateStatus(`Erreur : ${err.message}.`, 0);
        this.isLoading = false;
        this.searchError = `Échec du chargement de la base de données : ${err.message}`;
      }
    },

    updateStatus(message, progress) {
      this.loadingStatus = message;
      if (progress !== undefined) {
        this.loadingProgress = progress;
      }
    },

    formatSearchTerm(term) {
      return term.trim()
        .split(/\s+/)
        .filter(part => part.length > 0)
        .map(part => part.replace(/[^a-zA-Z0-9\u00C0-\u017F]/g, ' ').trim())
        .filter(part => part.length > 0)
        .map(part => part + '*')
        .join(' ');
    },

    async performSearch() {
      if (!this.dbLoaded || !this.db) {
        this.searchError = "Base de données non prête.";
        return;
      }
      if (!this.searchTerm.trim()) {
        this.searchResults = [];
        this.resultCountText = '';
        this.searchError = null;
        this.searchPerformed = false;
        return;
      }

      this.activeFilter = 'all';
      this.searchError = null;
      this.searchPerformed = true;
      this.lastSearchedTerm = this.searchTerm;
      this.searchInProgress = true;
      this.resultCountText = 'Recherche en cours...';

      const ftsQueryTerm = this.formatSearchTerm(this.searchTerm);

      if (!ftsQueryTerm) {
        this.searchError = "Terme de recherche invalide après formatage.";
        this.searchResults = [];
        this.resultCountText = '0 résultats';
        this.searchInProgress = false;
        return;
      }

      try {
        await new Promise(resolve => setTimeout(resolve, 10));

        const query = `
          SELECT
              rowid,
              CODE_PCT, Nom_Commercial, Dosage, DCI, AMM, Laboratoire,
              PRIX_PUBLIC, TARIF_REFERENCE, CATEGORIE, AP,
              Indications, Classe_Therapeutique, Sous_Classe_Therapeutique,
              Forme, Presentation, Date_AMM, VEIC, Tableau,
              Duree_Conservation, Conditionnement_Primaire, Specification_Conditionnement,
              RCP_Link, Notice_Link
          FROM "${this.ftsTableName}"
          WHERE "${this.ftsTableName}" MATCH ?
          ORDER BY rank
        `;

        const stmt = this.db.prepare(query);
        stmt.bind([ftsQueryTerm]);

        const results = [];
        while (stmt.step()) {
          let row = stmt.getAsObject();
          row.PRIX_PUBLIC_formatted = row.PRIX_PUBLIC ? parseFloat(row.PRIX_PUBLIC).toFixed(3) : 'N/A';
          row.TARIF_REFERENCE_formatted = row.TARIF_REFERENCE ? parseFloat(row.TARIF_REFERENCE).toFixed(3) : 'N/A';
          results.push(row);
        }
        stmt.free();

        this.searchResults = results;
        const count = results.length;
        this.resultCountText = `${count >= 100 ? 100 : count}${count >= 100 ? '+' : ''} résultat${count > 1 ? 's' : ''} trouvé${count > 1 ? 's' : ''}.`;

      } catch (err) {
        let errorMsg = err.message;
        if (errorMsg.includes('malformed MATCH expression') || errorMsg.includes('fts5 syntax error')) {
          errorMsg = 'Syntaxe de recherche invalide. Essayez d\'utiliser des mots simples ou des parties de mots.';
        }
        this.searchError = `Erreur de recherche : ${errorMsg}`;
        this.searchResults = [];
        this.resultCountText = '';
      } finally {
        this.searchInProgress = false;
      }
    },

    resetFilterIfNeeded() {
      if (!this.searchTerm.trim()) {
        this.searchResults = [];
        this.activeFilter = 'all';
        this.searchPerformed = false;
        this.resultCountText = '';
        this.searchError = null;
      }
    }
  };
};
</script>

<style>
/* Base Styles */
h1, h2 {
  text-align: center;
}

#medicament-search {
  width: 100%;
}

/* Loading Indicators */
.loading-section {
  text-align: center;
  padding: 20px 10px;
  background-color: var(--bg1);
  border-radius: 5px;
  margin-bottom: 1.5rem;
  color: var(--fg2);
}

.loading-status-text {
  font-weight: bold;
  color: var(--fg1);
  margin-bottom: 10px;
}

.loading-progress-bar {
  -webkit-appearance: none;
  appearance: none;
  width: 80%;
  max-width: 300px;
  height: 8px;
  border: 1px solid var(--bg4);
  background-color: var(--bg2);
  border-radius: 4px;
  overflow: hidden;
  display: block;
  margin: 10px auto 0 auto;
}

/* Progress bar styling */
.loading-progress-bar::-webkit-progress-bar {
  background-color: var(--bg2);
  border-radius: 4px;
}
.loading-progress-bar::-webkit-progress-value {
  background-color: var(--orange);
  border-radius: 4px;
  transition: width 0.2s ease;
}
.loading-progress-bar::-moz-progress-bar {
  background-color: var(--orange);
  border-radius: 4px;
  transition: width 0.2s ease;
}

.loading-spinner {
  display: inline-block;
  margin-top: 10px;
  font-size: 1.5em;
  color: var(--orange);
  animation: spin 1.5s linear infinite;
}

/* Search Results Loading Indicator */
.results-loading-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 15px;
  background-color: var(--bg1);
  border-radius: 5px;
  margin-bottom: 1rem;
  color: var(--fg1);
  font-style: italic;
}

.rotating-icon {
  display: inline-block;
  margin-right: 10px;
  font-size: 1.2em;
  animation: spin 1.5s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Search UI */
.search-ui-section {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  padding: 15px 10px;
  background-color: var(--bg1);
  border-radius: 5px;
  margin-bottom: 0.5rem;
}

.search-input {
  flex-grow: 1;
  min-width: 200px;
  padding-left: 0.75rem;
  padding-right: 0.75rem;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  border: 1px solid var(--bg4);
  border-radius: 4px;
  background-color: var(--bg);
  color: var(--fg);
  font-size: 1em;
}
.search-input:focus {
  outline: 2px solid var(--blue);
  border-color: var(--blue);
}

.search-button {
  padding: 10px 15px;
  background-color: var(--blue);
  color: var(--bg);
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
  transition: background-color 0.15s ease;
}

.search-button:hover:not(:disabled) {
  background-color: var(--blue-dim);
}
.search-button:disabled {
  background-color: var(--bg4);
  color: var(--fg4);
  cursor: not-allowed;
}

.result-count-text {
  width: 100%;
  text-align: center;
  margin-top: 0.5rem;
  font-size: 0.9em;
  color: var(--fg3);
  font-style: italic;
}

/* Filters */
.filter-controls {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding: 5px 10px;
  text-align: center;
  background-color: var(--bg2);
  border-radius: 4px;
}

.filter-controls span:first-child {
  color: var(--fg1);
  font-weight: bold;
  margin-right: 5px;
}

.filter-count {
  font-size: 0.9em;
  color: var(--fg3);
  margin-left: 10px;
}

.filter-button {
  padding: 5px 10px;
  border: 1px solid var(--bg4);
  background-color: var(--bg);
  color: var(--fg);
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9em;
  transition: background-color 0.15s ease, border-color 0.15s ease;
}

.filter-button:hover {
  background-color: var(--bg3);
  border-color: var(--fg4);
}

.filter-button.active-filter-button {
  background-color: var(--green);
  color: var(--bg);
  border-color: var(--green);
  font-weight: bold;
}

/* Results Section */
.results-section {
  margin-top: 1rem;
}

.result-item {
  background-color: var(--bg1);
  border: 1px solid var(--bg3);
  border-radius: 5px;
  padding: 15px;
  margin-bottom: 1rem;
}

.result-header {
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.result-title {
  flex: 1;
}

.result-details {
  margin-top: 10px;
}

/* Details section with dividers */
.details-section {
  margin-bottom: 8px;
  padding-bottom: 8px;
  border-bottom: 1px solid var(--bg3);
}

/* Special styling for the last visible section */
.details-section.last-visible-section {
  margin-bottom: 0 !important;
  padding-bottom: 0 !important;
  border-bottom: none !important;
}

.result-item .prop-value-nom-commercial {
  margin-top: 0;
  font-size: 1.1rem;
}

.result-item .title-main {
  color: var(--red);
  font-weight: 700;
}

.result-item .title-extra {
  font-weight: 400;
  color: var(--fg2);
}

.field-container {
  padding-bottom: 0.25rem;
  padding-top: 0.25rem;
}

.prop-label {
  color: var(--blue);
  margin-right: 5px;
}

.prop-label-prix-public {
  color: var(--orange) !important;
}

.prop-value-prix-public {
  font-weight: 700;
}

.result-item a {
  color: var(--purple);
  font-weight: bold;
}

.result-item a:hover {
  color: var(--blue-dim);
}

/* Arrow Indicator */
.arrow-indicator {
  display: inline-block;
  font-size: 0.8em;
  color: var(--fg3);
  transition: transform 0.2s ease-in-out;
  margin-left: 0.25rem;
}

.arrow-indicator.rotate-90 {
  transform: rotate(90deg);
}

/* Messages */
.search-error-message,
.no-results-message {
  text-align: center;
  color: var(--orange);
  font-style: italic;
  background-color: var(--bg2);
  padding: 10px;
  border-radius: 4px;
  border: 1px solid var(--orange-dim);
}

.no-results-message span {
  font-weight: bold;
  font-style: normal;
}

/* Hide elements managed by Alpine initially */
[x-cloak] {
  display: none !important;
}

h1{
  margin-bottom: 1rem;
}
</style>
