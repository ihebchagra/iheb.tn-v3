<div style="display: none;" href='/apiprofil' id="metadata">APIprofil - iheb.tn</div>

<h1>🔬 APIprofil</h1>

<div id="apiprofil-app" x-data="apiProfilApp()" x-init="init()" class="apiprofil-container">
  <!-- HTML is UNCHANGED -->
  <div class="notice">
    <b>Remarque : </b> Les données proviennent des tables d'identification API. Elles représentent la probabilité (%) d'une réaction positive pour un taxon donné.
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

  <template x-if="!dbLoading && !dbError && db.length">
    <div class="apiprofil-search-section">
      <div class="autocomplete-container">
        <input
          type="search"
          id="search-term-input"
          placeholder="Rechercher un taxon et appuyer sur Entrée"
          x-model="searchTerm"
          @keydown="handleAutocompleteKeydown($event)"
          @keydown.enter.prevent="showAllMatches()"
          @input="handleSearchInput()"
          @focus="updateAutocomplete()"
          @click.away="closeAutocomplete()"
          class="search-input"
          aria-label="Terme de recherche">

        <div x-show="showAutocomplete" class="autocomplete-dropdown">
          <ul x-show="autocompleteResults.length > 0">
            <template x-for="(bacterium, index) in autocompleteResults" :key="bacterium.Taxon">
              <li
                @click="selectSingleBacterium(bacterium)"
                @mouseenter="selectedAutocompleteIndex = index"
                :class="{ 'selected': selectedAutocompleteIndex === index }"
                x-html="highlightMatch(bacterium.Taxon, searchTerm)"></li>
            </template>
          </ul>
           <div x-show="autocompleteResults.length === 0 && searchTerm.length > 1" class="autocomplete-loading">
                Aucun taxon correspondant.
           </div>
        </div>
      </div>
      <button @click="resetSearch()" class="apiprofil-reset-button" title="Réinitialiser la recherche">✖</button>
    </div>
  </template>

  <!-- MODIFIED: The lookup now happens on the `normalized_props` object -->
  <div class="apiprofil-results-container" x-show="displayedResults.length > 0" x-transition>
      <template x-for="bacterium in displayedResults" :key="bacterium.Taxon">
        <div class="apiprofil-result">
          <h2 class="result-title" x-text="bacterium.Taxon"></h2>
          <div class="profile-grid">
              <template x-for="crit in criteria" :key="crit.key">
                  <div class="profile-item">
                      <div class="profile-item-label">
                          <span x-text="crit.label"></span>
                          <span class="criterion-abbr" x-text="crit.key"></span>
                      </div>
                      <div class="profile-item-value"
                          :class="getProfileValueColor(bacterium.normalized_props[normalizeKey(crit.key)])"
                          x-text="getProfileValueText(bacterium.normalized_props[normalizeKey(crit.key)])">
                      </div>
                  </div>
              </template>
          </div>
        </div>
      </template>
  </div>

  <template x-if="!dbLoading && displayedResults.length === 0 && db.length > 0">
      <div class="apiprofil-placeholder">
          <p>Sélectionnez une base de données, commencez à taper le nom d'un taxon, puis appuyez sur <b>Entrée</b> pour voir le(s) profil(s) biochimique(s).</p>
      </div>
  </template>

</div>

<script>
  function apiProfilApp() {
    return {
      // All state properties are unchanged
      dbOptions: [{ key: 'api10s', label: 'API 10S' }, { key: 'api20e', label: 'API 20E' }, { key: 'api20ne', label: 'API 20NE' }, { key: 'apistrep', label: 'API Strep' }, { key: 'apicoryne', label: 'API Coryne' }, { key: 'apinh', label: 'API NH' }, { key: 'apistaph', label: 'API Staph' }, { key: 'id32c', label: 'ID32 C' }],
      selectedDb: 'api10s',
      dbLoading: false,
      dbError: null,
      db: [], 
      criteria: [], 
      searchTerm: '',
      showAutocomplete: false,
      autocompleteResults: [],
      selectedAutocompleteIndex: -1,
      displayedResults: [],

      // --- Core Methods ---
      // init(), selectDb(), loadDb() are unchanged
      async init() {
        const savedDb = localStorage.getItem('apiprofil-selected-db');
        if (savedDb) this.selectedDb = savedDb;
        await this.loadDb(this.selectedDb);
      },
      async selectDb(key) {
        if (this.selectedDb === key) return;
        this.selectedDb = key;
        localStorage.setItem('apiprofil-selected-db', key);
        this.resetSearch();
        await this.loadDb(key);
      },
      async loadDb(key) {
        this.dbLoading = true; this.dbError = null; this.criteria = []; this.db = []; this.resetSearch();
        let url = '';
        if (key === 'api10s') url = '/assets/db/api10s.json?v=1';
        else if (key === 'api20e') url = '/assets/db/api20e.json?v=12';
        else if (key === 'api20ne') url = '/assets/db/api20ne.json?v=12';
        else if (key === 'apistrep') url = '/assets/db/apistrep.json?v=14';
        else if (key === 'apicoryne') url = '/assets/db/apicoryne.json?v=16';
        else if (key === 'apinh') url = '/assets/db/apinh.json?v=14';
        else if (key === 'apistaph') url = '/assets/db/apistaph.json?v=14';
        else if (key === 'id32c') url = '/assets/db/id32c.json?v=10';
        else { this.dbError = 'Base inconnue.'; this.dbLoading = false; return; }
        try {
          const resp = await fetch(url);
          if (!resp.ok) throw new Error('Erreur de chargement');
          const data = await resp.json();
          this.db = this.createSearchIndex(data.db);
          this.criteria = data.criteria;
        } catch (e) {
            if (key === 'api20e') this.dbError = "API 20E : Bientôt disponible.";
            else if (key === 'api20ne') this.dbError = "API 20NE : Bientôt disponible.";
            else if (key === 'apistrep') this.dbError = "API Strep : Bientôt disponible.";
            else if (key === 'apicoryne') this.dbError = "API Coryne : Bientôt disponible.";
            else if (key === 'apinh') this.dbError = "API NH : Bientôt disponible.";
            else if (key === 'apistaph') this.dbError = "API Staph : Bientôt disponible.";
            else if (key === 'id32c') this.dbError = "ID32 C  : Bientôt disponible.";
            else this.dbError = "Erreur de chargement de la base.";
        }
        this.dbLoading = false;
      },
      
      // *** NEW: The normalization function ***
      normalizeKey(key) {
        if (!key) return '';
        // Converts "ß-GUR" or "ßGUR" to "bgur"
        return key.toLowerCase().replace(/[^a-z0-9]/g, '');
      },

      // *** MODIFIED: createSearchIndex now also creates a normalized properties object ***
      createSearchIndex(database) {
        if (!database) return [];
        return database.map(bacterium => {
          // Normalize taxon name for search
          const name = bacterium.Taxon.toLowerCase();
          const tokens = name.split(/[\s./-]+/).filter(t => t.length > 0);
          
          // Normalize all property keys for data lookup
          const normalizedProps = {};
          for (const key in bacterium) {
            normalizedProps[this.normalizeKey(key)] = bacterium[key];
          }

          return { 
            ...bacterium, // Keep original data
            search_tokens: tokens, 
            search_acronym: tokens.map(t => t[0]).join(''),
            normalized_props: normalizedProps, // Add the new object with clean keys
          };
        });
      },

      // The rest of the JS functions are unchanged.
      handleSearchInput() { this.displayedResults = []; this.updateAutocomplete(); },
      updateAutocomplete() {
        if (this.searchTerm.trim().length < 1) { this.autocompleteResults = []; this.showAutocomplete = false; return; }
        const searchTokens = this.searchTerm.toLowerCase().trim().split(/\s+/).filter(t => t.length > 0);
        const isAcronymSearch = searchTokens.length === 1;
        this.autocompleteResults = this.db.filter(bacterium => {
            if (isAcronymSearch && bacterium.search_acronym.startsWith(searchTokens[0])) return true;
            return searchTokens.every(sToken => 
              bacterium.search_tokens.some(bToken => bToken.startsWith(sToken) || sToken.startsWith(bToken))
            );
          }).slice(0, 10);
        this.showAutocomplete = true;
      },
      showAllMatches() { this.displayedResults = this.autocompleteResults; this.closeAutocomplete(); },
      selectSingleBacterium(bacterium) { this.displayedResults = [bacterium]; this.searchTerm = bacterium.Taxon; this.closeAutocomplete(); },
      resetSearch() { this.searchTerm = ''; this.displayedResults = []; this.closeAutocomplete(); },
      handleAutocompleteKeydown(event) {
        if (!this.showAutocomplete) return;
        if (event.key === 'ArrowDown') { event.preventDefault(); this.selectedAutocompleteIndex = Math.min(this.selectedAutocompleteIndex + 1, this.autocompleteResults.length - 1); } 
        else if (event.key === 'ArrowUp') { event.preventDefault(); this.selectedAutocompleteIndex = Math.max(this.selectedAutocompleteIndex - 1, 0); } 
        else if (event.key === 'Enter') { this.showAllMatches(); } 
        else if (event.key === 'Escape') { this.closeAutocomplete(); }
      },
      getProfileValueText(value) {
          if (value === undefined || value === null) return 'N/A';
          return `${value}%`;
      },
      getProfileValueColor(value) {
        if (value === undefined || value === null) return 'color-na';
        if (value >= 85) return 'color-high';
        if (value >= 50) return 'color-mid';
        if (value >= 15) return 'color-low';
        return 'color-vlow';
      },
      highlightMatch(text, term) {
        if (!text || !term) return text;
        const searchTerms = term.trim().split(/\s+/).join('|');
        if (!searchTerms) return text;
        const regex = new RegExp('(' + searchTerms.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')', 'gi');
        return text.replace(regex, '<strong class="highlight">$1</strong>');
      },
      closeAutocomplete() { this.showAutocomplete = false; this.selectedAutocompleteIndex = -1; },
    };
  }
</script>

<!-- The <style> block is unchanged -->
<style>
  h1, h2 { text-align: center; }
  .apiprofil-container { background: var(--bg1); border-radius: 8px; padding: 1.5rem 1rem; width: calc(100% - 2rem); box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
  .notice { font-size: 0.9em; color: var(--fg4); margin-bottom: 1rem; text-align: center; }
  .apicalcul-db-select { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; justify-content: center; margin-bottom: 1.2rem; }
  .apicalcul-db-select button { border: none; border-radius: 4px; padding: 0.3em 1em; font-size: 1em; font-weight: bold; background: var(--bg3); color: var(--fg2); cursor: pointer; transition: background 0.15s, color 0.15s; outline: none; }
  .apicalcul-db-select button.active { background: var(--green); color: var(--bg); }
  .apicalcul-db-select button:not(.active):hover { background: var(--green-dim); color: var(--bg); }
  .apicalcul-loading { text-align: center; color: var(--blue); font-weight: bold; margin-bottom: 1rem; }
  .apicalcul-error { text-align: center; color: var(--red); font-weight: bold; margin-bottom: 1rem; }
  .criterion-abbr { font-size: 0.85em; color: var(--gray); margin-left: 0.4em; }
  .apiprofil-search-section { display: flex; gap: 0.5rem; align-items: center; padding: 10px; background-color: var(--bg2); border-radius: 5px; margin-bottom: 1.5rem; }
  .autocomplete-container { position: relative; flex-grow: 1; display: flex; }
  .search-input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--bg4); border-radius: 4px; background-color: var(--bg); color: var(--fg); font-size: 1em; }
  .search-input:focus { outline: 2px solid var(--blue); border-color: var(--blue); }
  .apiprofil-reset-button { background: var(--bg3); color: var(--red); border: none; border-radius: 50%; width: 38px; height: 38px; font-size: 1.2em; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background-color 0.15s ease; }
  .apiprofil-reset-button:hover { background-color: var(--bg4); }
  .autocomplete-dropdown { position: absolute; top: 100%; left: 0; right: 0; z-index: 10; max-height: 300px; overflow-y: auto; background-color: var(--bg); border: 1px solid var(--bg4); border-radius: 4px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-top: 4px; }
  .autocomplete-dropdown ul { list-style: none; padding: 0; margin: 0; }
  .autocomplete-dropdown li { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid var(--bg2); transition: background-color 0.15s ease; }
  .autocomplete-dropdown li:last-child { border-bottom: none; }
  .autocomplete-dropdown li:hover, .autocomplete-dropdown li.selected { background-color: var(--bg2); }
  .autocomplete-dropdown .highlight { color: var(--blue); font-weight: bold; background: transparent; }
  .autocomplete-loading { padding: 10px; text-align: center; color: var(--fg3); font-style: italic; }
  .apiprofil-results-container { display: flex; flex-direction: column; gap: 1.5rem; }
  .apiprofil-result { background: var(--bg2); border-radius: 6px; padding: 1rem 1.5rem; box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
  .result-title { margin-top: 0; margin-bottom: 1.5rem; color: var(--orange); }
  .profile-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; }
  .profile-item { background: var(--bg1); border-radius: 5px; padding: 0.8rem 1rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
  .profile-item-label { font-weight: bold; color: var(--fg2); }
  .profile-item-value { font-weight: bold; font-size: 1.1em; padding: 0.2em 0.6em; border-radius: 4px; border: 1px solid; background-color: var(--bg1); }
  .apiprofil-placeholder { text-align: center; padding: 2rem; color: var(--fg3); background-color: var(--bg2); border-radius: 6px; border: 2px dashed var(--bg3); }
  .profile-item-value.color-high { border-color: var(--green); color: var(--green); }
  .profile-item-value.color-mid { border-color: var(--blue); color: var(--blue); }
  .profile-item-value.color-low { border-color: var(--orange); color: var(--orange); }
  .profile-item-value.color-vlow { border-color: var(--red); color: var(--red); }
  .profile-item-value.color-na { border-color: var(--gray); color: var(--fg3); background-color: var(--bg2); }
</style>
