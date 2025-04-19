<div style="display: none;" href="/casfmsearch<?= $_GET['q'] ? '?q=' . $_GET['q'] : '' ?>" id="metadata">CA-SFM Search - iheb.tn</div>

<h1>CA-SFM Search</h1>

<!-- Search Component Container -->
<div id="casfm-search" x-data="casfmSearchApp()" x-init="initLoader()">
    <!-- Loading Database Indicator -->
    <div x-show="isLoading" class="loading-section">
        <p x-text="loadingStatus" class="loading-status-text"></p>
        <progress x-show="loadingProgress > 0 && loadingProgress < 100" x-bind:value="loadingProgress" max="100" class="loading-progress-bar" aria-label="Progression du chargement de la base de données"></progress>
        <span x-show="isLoading && loadingProgress >= 100" class="loading-spinner" aria-hidden="true">⚙️</span>
        <span x-show="isLoading && loadingProgress === 0" class="loading-spinner" aria-hidden="true">⏳</span>
    </div>

    <!-- Search UI (Shown after loading) -->
    <div x-show="dbLoaded" class="search-ui-section" x-transition>
        <input type="search" id="search-term-input" placeholder="Rechercher les recommendations..." x-model="searchTerm" @keydown.enter="performSearch()" @input="handleInput()" class="search-input" aria-label="Terme de recherche" />
        <button @click="performSearch()" class="search-button" :disabled="isLoading || !searchTerm.trim()" aria-label="Effectuer la recherche">Rechercher</button>
    </div>

    <!-- Default View: Table of Contents (Loaded from DB) -->
    <div x-show="showDefaultView && !isLoading && dbLoaded && tocData.length > 0" class="toc-section results-section" x-transition.opacity>
        <h2>Table des Matières</h2>
        <ul class="toc-list">
            <template x-for="(chapter, c_idx) in tocData" :key="'c'+c_idx">
                <li>
                    <a x-bind:hx-get="'/casfm-viewer?p=' + chapter.page" hx-target="#content" hx-swap="outerHTML" hx-select="#content" class="toc-link toc-chapter">
                        <strong x-text="chapter.title + (chapter.page ? ` (p. ${chapter.page})` : '')"></strong>
                    </a>
                    <template x-if="chapter.subchapters && chapter.subchapters.length > 0">
                        <ul class="toc-sub-list">
                            <template x-for="(subchapter, sc_idx) in chapter.subchapters" :key="'sc'+c_idx+sc_idx">
                                <li>
                                    <a x-bind:hx-get="'/casfm-viewer?p=' + subchapter.page" hx-target="#content" hx-swap="outerHTML" hx-select="#content" class="toc-link toc-subchapter">
                                        <span x-text="subchapter.title + (subchapter.page ? ` (p. ${subchapter.page})` : '')"></span>
                                    </a>
                                    <template x-if="subchapter.subsections && subchapter.subsections.length > 0">
                                        <ul class="toc-sub-sub-list">
                                            <template x-for="(subsection, ssc_idx) in subchapter.subsections" :key="'ssc'+c_idx+sc_idx+ssc_idx">
                                                <li>
                                                    <a x-bind:hx-get="'/casfm-viewer?p=' + subsection.page" hx-target="#content" hx-swap="outerHTML" hx-select="#content" class="toc-link toc-subsection">
                                                        <span x-text="subsection.title + (subsection.page ? ` (p. ${subsection.page})` : '')"></span>
                                                    </a>
                                                </li>
                                            </template>
                                        </ul>
                                    </template>
                                </li>
                            </template>
                        </ul>
                    </template>
                </li>
            </template>
        </ul>
    </div>
    <div x-show="showDefaultView && !isLoading && dbLoaded && tocData.length === 0" class="toc-section results-section">
        <p>Table des matières non disponible.</p>
    </div>

    <!-- Search Results Section -->
    <div id="search-results-container" x-show="!showDefaultView" class="results-section" aria-live="polite">
        <!-- Loading Results Indicator -->
        <div x-show="searchPerformed && !searchError && !isLoading && searchInProgress" class="results-loading-indicator">
            <span class="rotating-icon" aria-hidden="true">🔍</span>
            <span>Recherche en cours...</span>
        </div>

        <!-- Error Message -->
        <p x-show="searchError" x-text="searchError" class="search-error-message"></p>

        <!-- No Results Message -->
        <p x-show="!isLoading && searchPerformed && searchResults.length === 0 && !searchError && !searchInProgress" class="no-results-message">Aucun résultat trouvé pour "<span x-text="lastSearchedTerm"></span>".</p>

        <!-- Results list section -->
        <template x-for="result in searchResults.slice(0, 100)" :key="result.rowid">
            <!-- Make the entire item a clickable link -->
            <a x-bind:hx-get="'/casfm-viewer?q=' + searchTerm + '&p=' + result.page" hx-target="#content" hx-swap="outerHTML" hx-select="#content" class="result-item-link">
                <div class="result-item-content">
                    <!-- Header: Page Number -->
                    <div class="result-header">
                        <span class="page-number">Page <span x-text="result.page"></span></span>
                    </div>
                    <!-- Details: Chapter, Subchapter, Subsection -->
                    <div class="result-details-hierarchy">
                        <template x-if="result.chapter">
                            <div class="field-container">
                                <span class="prop-value prop-value-chapter" x-text="result.chapter"></span>
                            </div>
                        </template>
                        <template x-if="result.subchapter">
                            <div class="field-container">
                                <span class="prop-value prop-value-subchapter" x-text="result.subchapter"></span>
                            </div>
                        </template>
                        <template x-if="result.subsection">
                            <div class="field-container">
                                <span class="prop-value prop-value-subsection" x-text="result.subsection"></span>
                            </div>
                        </template>
                    </div>
                    <!-- Details: Context/Snippet -->
                    <div class="result-details-content">
                        <div class="prop-value prop-value-content-snippet" x-html="result.snippet"></div>
                    </div>
                </div>
            </a>
        </template>
        <p x-show="!showDefaultView && searchResults.length > 100" class="result-limit-message">
            Affichage des 100 premiers résultats. Affinez votre recherche pour des correspondances plus spécifiques.
        </p>
    </div>
</div>
<!-- End Search Component Container -->

<script>
    // Ensure Alpine and initSqlJs are available globally before this runs
    
    function casfmSearchApp() {
          return {
            // State Variables
            isLoading: true,
            loadingStatus: "Initialisation...",
            loadingProgress: 0,
            dbLoaded: false,
            db: null,
            SQL: null, // Will be assigned from global initSqlJs
            searchTerm: "<?= $_GET['q'] ?>",
            lastSearchedTerm: "",
            searchResults: [],
            searchError: null,
            searchPerformed: false,
            searchInProgress: false,
            showDefaultView: true, // Show ToC by default
            tocData: [], // To hold Table of Contents data from DB

            // Configuration
            dbPath: "/assets/db/casfm_fts5.db", // Mettez à jour le chemin si nécessaire
            ftsTableName: "pdf_content",

            // Methods
            async initLoader() {
                this.isLoading = true;
                this.dbLoaded = false;
                this.searchError = null;
                this.loadingProgress = 0;
                this.tocData = []; // Reset ToC data

                try {
                    this.updateStatus("Initialisation...", 5);

                    // Check if initSqlJs is globally available
                    if (typeof initSqlJs !== "function") {
                        throw new Error("Bibliothèque SQL.js (initSqlJs) non trouvée. Assurez-vous qu'elle est chargée globalement.");
                    }

                    // Load sql.js (assuming locateFile path is correct globally or handled elsewhere)
                    this.SQL = await initSqlJs({ locateFile: (filename) => `/${filename}` }); // Use global
                    this.updateStatus("Récupération du fichier de base de données...", 15);

                    // Fetch database
                    const dbResponse = await fetch(this.dbPath);
                    if (!dbResponse.ok) {
                        throw new Error(`Échec de la récupération de la base de données: ${dbResponse.statusText} (Statut: ${dbResponse.status})`);
                    }

                    const contentLength = dbResponse.headers.get("Content-Length");
                    const totalSize = contentLength ? parseInt(contentLength, 10) : null;
                    let baseProgress = 15;

                    if (!totalSize) {
                        this.updateStatus("Taille de la base de données inconnue. Chargement...", baseProgress);
                    } else {
                        this.updateStatus(`Téléchargement de la base de données (${(totalSize / 1024 / 1024).toFixed(2)} Mo)...`, baseProgress);
                    }

                    if (!dbResponse.body) {
                        throw new Error("ReadableStream non supporté ou la réponse fetch manque de corps.");
                    }
                    const reader = dbResponse.body.getReader();
                    let bytesLoaded = 0;
                    let chunks = [];
                    while (true) {
                        const { done, value } = await reader.read();
                        if (done) break;
                        chunks.push(value);
                        bytesLoaded += value.length;
                        if (totalSize) {
                            const progress = (bytesLoaded / totalSize) * (95 - baseProgress);
                            this.loadingProgress = Math.min(95, baseProgress + progress);
                            this.updateStatus(`Téléchargement... ${(bytesLoaded / 1024 / 1024).toFixed(2)} Mo / ${(totalSize / 1024 / 1024).toFixed(2)} Mo`);
                        } else {
                            this.updateStatus(`Chargement... ${(bytesLoaded / 1024 / 1024).toFixed(2)} Mo chargés`);
                        }
                    }
                    this.updateStatus("Téléchargement terminé. Traitement...", 95);

                    const finalTotalSize = totalSize || bytesLoaded;
                    let dbArray = new Uint8Array(finalTotalSize);
                    let offset = 0;
                    for (const chunk of chunks) {
                        dbArray.set(chunk, offset);
                        offset += chunk.length;
                    }
                    chunks = [];

                    this.updateStatus("Chargement de la base de données en mémoire...", 98);
                    this.db = new this.SQL.Database(dbArray); // Use the loaded DB
                    this.updateStatus("Base de données prête ! Chargement de la Table des Matières...", 99);
                    this.dbLoaded = true; // Mark DB as loaded BEFORE loading ToC

                    // Load Table of Contents from the database
                    await this.loadTableOfContents();

                    this.updateStatus("Prêt !", 100);
                    this.isLoading = false;
                    // if searchTerm is not empty, perform search
                    if (this.searchTerm.trim()) {
                        this.performSearch();
                    }
                    this.$nextTick(() => {
                      htmx.process(document.body);
                    });
                } catch (err) {
                    this.updateStatus(`Erreur : ${err.message}.`, 0);
                    this.isLoading = false;
                    this.searchError = `Échec du chargement des données nécessaires : ${err.message}. Veuillez essayer de rafraîchir la page.`;
                    this.dbLoaded = false;
                    this.showDefaultView = false; // Hide ToC view on critical error
                }
            },

            async loadTableOfContents() {
                if (!this.db) {
                    this.tocData = []; // Ensure it's empty
                    return;
                }
                const tocStart = performance.now();

                try {
                    const chapters = [];
                    // 1. Get distinct chapters and their starting page
                    const chapterQuery = `
                    SELECT DISTINCT chapter, MIN(page) as page
                    FROM ${this.ftsTableName}
                    WHERE chapter IS NOT NULL AND chapter != 'Unknown'
                    GROUP BY chapter
                    ORDER BY MIN(page);
                `;
                    const chapterStmt = this.db.prepare(chapterQuery);
                    while (chapterStmt.step()) {
                        const chapterRow = chapterStmt.getAsObject();
                        chapters.push({
                            title: chapterRow.chapter,
                            page: chapterRow.page,
                            subchapters: [], // Initialize subchapters array
                        });
                    }
                    chapterStmt.free();

                    // 2. For each chapter, get its distinct subchapters
                    const subchapterQuery = `
                    SELECT DISTINCT subchapter, MIN(page) as page
                    FROM ${this.ftsTableName}
                    WHERE chapter = ? AND subchapter IS NOT NULL
                    GROUP BY subchapter
                    ORDER BY MIN(page);
                `;
                    const subsectionQuery = `
                    SELECT DISTINCT subsection, MIN(page) as page
                    FROM ${this.ftsTableName}
                    WHERE chapter = ? AND subchapter = ? AND subsection IS NOT NULL
                    GROUP BY subsection
                    ORDER BY MIN(page);
                `;

                    for (let chapter of chapters) {
                        const subStmt = this.db.prepare(subchapterQuery);
                        subStmt.bind([chapter.title]);
                        while (subStmt.step()) {
                            const subRow = subStmt.getAsObject();
                            const subchapter = {
                                title: subRow.subchapter,
                                page: subRow.page,
                                subsections: [], // Initialize subsections array
                            };

                            // 3. For each subchapter, get its distinct subsections
                            const subSubStmt = this.db.prepare(subsectionQuery);
                            subSubStmt.bind([chapter.title, subchapter.title]);
                            while (subSubStmt.step()) {
                                const subSubRow = subSubStmt.getAsObject();
                                subchapter.subsections.push({
                                    title: subSubRow.subsection,
                                    page: subSubRow.page,
                                });
                            }
                            subSubStmt.free();

                            chapter.subchapters.push(subchapter);
                        }
                        subStmt.free();
                    }

                    this.tocData = chapters;
                    const tocEnd = performance.now();
                } catch (err) {
                    this.searchError = "Erreur lors du chargement de la table des matières.";
                    this.tocData = []; // Ensure it's empty on error
                }
            },

            updateStatus(message, progress) {
                this.loadingStatus = message;
                if (progress !== undefined) {
                    this.loadingProgress = Math.min(100, Math.max(0, progress));
                }
            },

            formatSearchTerm(term) {
                return term
                    .trim()
                    .split(/\s+/)
                    .map((part) => part.replace(/[^a-zA-Z0-9\u00C0-\u017F'-]/g, " ").trim())
                    .filter((part) => part.length > 0)
                    .map((part) => part + "*")
                    .join(" ");
            },

            async performSearch() {
                if (!this.dbLoaded || !this.db) {
                    this.searchError = "Base de données non prête.";
                    return;
                }
                const trimmedTerm = this.searchTerm.trim();
                if (!trimmedTerm) {
                    this.handleInput();
                    return;
                }

                this.showDefaultView = false; // Hide ToC when searching
                this.searchError = null;
                this.searchPerformed = true;
                this.lastSearchedTerm = this.searchTerm;
                this.searchInProgress = true;
                this.searchResults = [];

                const ftsQueryTerm = this.formatSearchTerm(trimmedTerm);
                if (!ftsQueryTerm) {
                    this.searchError = "Terme de recherche invalide après formatage.";
                    this.searchInProgress = false;
                    return;
                }

                try {
                    await new Promise((resolve) => setTimeout(resolve, 10));
                    const query = `
                    SELECT rowid, page, chapter, subchapter, subsection,
                           snippet(${this.ftsTableName}, 1, '<b>', '</b>', '...', 20) as snippet
                    FROM "${this.ftsTableName}"
                    WHERE "${this.ftsTableName}" MATCH ?
                    ORDER BY rank
                    LIMIT 101`;
                    const stmt = this.db.prepare(query);
                    stmt.bind([ftsQueryTerm]);
                    const results = [];
                    while (stmt.step()) {
                        results.push(stmt.getAsObject());
                    }
                    stmt.free();
                    this.searchResults = results;
                } catch (err) {
                    let errorMsg = err.message;
                    if (errorMsg.includes("malformed MATCH") || errorMsg.includes("fts5 syntax error")) {
                        errorMsg = "Syntaxe de recherche invalide.";
                    } else if (errorMsg.includes("snippet")) {
                        errorMsg = "Erreur de génération du contexte (snippet).";
                    }
                    this.searchError = `Erreur de recherche : ${errorMsg}`;
                    this.searchResults = [];
                } finally {
                    this.searchInProgress = false;
                    this.$nextTick(() => {
                      htmx.process(document.body);
                    });
                }
            },

            handleInput() {
                this.searchPerformed = false;
                this.searchError = null;
                if (!this.searchTerm.trim()) {
                    // If input is empty, show default view and clear results
                    this.showDefaultView = true;
                    this.searchResults = [];
                    this.lastSearchedTerm = "";
                } else {
                    // If input has content, hide default view
                    this.showDefaultView = false;
                }
            }
        }
    }
</script>

<style>
    /* --- Styles de Base (Conserver la plupart de medicasearch/casfmsearch) --- */
    h1,
    h2 {
        text-align: center;
    }
    #casfm-search {
        width: 100%;
    }

    /* --- Indicateurs de Chargement (Conserver tels quels) --- */
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
        from {
            transform: rotate(0deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    /* --- UI de Recherche (Conserver telle quelle) --- */
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

    /* --- Section des Résultats (Conserver styles précédents) --- */
    .results-section {
        margin-top: 1rem;
    }
    .result-item-link {
        display: block;
        text-decoration: none;
        color: inherit;
        background-color: var(--bg1);
        border: 1px solid var(--bg3);
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 1rem;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }
    .result-item-link:hover,
    .result-item-link:focus {
        background-color: var(--bg2);
        border-color: var(--bg4);
        outline: none;
    }
    .result-item-content {
        /* Pas de style spécifique */
    }
    .result-header {
        margin-bottom: 10px;
    }
    .page-number {
        font-weight: bold;
        color: var(--orange);
    }
    .page-number span {
        font-weight: normal;
    }
    .result-details-hierarchy {
        margin-bottom: 10px;
        padding-bottom: 8px;
        border-bottom: 1px dashed var(--bg3);
    }
    .result-details-hierarchy .field-container {
        margin-bottom: 4px;
    }
    .result-details-hierarchy .prop-label {
        color: var(--blue);
        margin-right: 5px;
        font-weight: bold;
        display: inline-block;
        min-width: 90px;
    }
    .prop-value-chapter {
        color: var(--red);
        font-weight: bold;
    }
    .prop-value-subchapter {
        color: var(--blue);
    }
    .prop-value-subsection {
        color: var(--green);
    }
    .result-details-content {
        margin-top: 10px;
    }
    .result-details-content .prop-label-content {
        color: var(--blue);
        display: block;
        margin-bottom: 3px;
        font-weight: bold;
    }
    .prop-value-content-snippet {
        line-height: 1.5;
        color: var(--fg);
    }
    .prop-value-content-snippet b {
        background-color: var(--yellow-dim);
        color: var(--bg);
        font-weight: bold;
        padding: 0 2px;
        border-radius: 2px;
    }

    /* --- Styles Table des Matières (Réintroduits) --- */
    .toc-section {
        background-color: var(--bg1);
        border: 1px solid var(--bg3);
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 1rem;
    }
    .toc-section h2 {
        margin-top: 0;
        margin-bottom: 1rem;
        color: var(--fg1);
    }
    .toc-list,
    .toc-sub-list,
    .toc-sub-sub-list {
        list-style: none;
        padding-left: 0;
    }

    .toc-list li {
        margin-bottom: 8px;
    }
    .toc-sub-list li {
        margin-bottom: 5px;
    }
    .toc-sub-sub-list li {
        margin-bottom: 3px;
    }

    .toc-link {
        text-decoration: none;
        color: var(--fg); /* Couleur de lien par défaut */
        display: inline-block; /* Permet au lien de ne pas prendre toute la largeur */
        padding: 2px 0; /* Petit padding vertical pour améliorer la cliquabilité */
        transition: color 0.15s ease;
    }
    .toc-link:hover {
        color: var(--blue); /* Couleur au survol */
        text-decoration: underline;
    }

    /* Styles spécifiques pour chaque niveau de la TdM */
    .toc-chapter strong {
        color: var(--red);
        font-size: 1.1em;
    }
    .toc-subchapter span {
        color: var(--blue);
        font-size: 1em;
    }
    .toc-subsection span {
        color: var(--green);
        font-size: 0.95em;
    }

    /* --- Messages (Conserver tels quels) --- */
    .search-error-message,
    .no-results-message {
        text-align: center;
        color: var(--orange);
        font-style: italic;
        background-color: var(--bg2);
        padding: 10px;
        border-radius: 4px;
        border: 1px solid var(--orange-dim);
        margin-bottom: 1rem;
    }
    .no-results-message span {
        font-weight: bold;
        font-style: normal;
    }
    .result-limit-message {
        text-align: center;
        color: var(--fg3);
        font-style: italic;
        margin-top: 1rem;
        font-size: 0.9em;
    }

    /* --- Divers --- */
    [x-cloak] {
        display: none !important;
    }
    h1 {
        margin-bottom: 1rem;
    }
    ul {
      padding-left: 0px;
      padding-right: 0px;
      margin-left: 0px;
      margin-right: 0px;
    }
</style>
