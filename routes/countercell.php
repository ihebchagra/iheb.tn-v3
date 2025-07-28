<div style="display: none;" href='/countercell' id="metadata">CounterCell - iheb.tn</div>

<h1>🧮 CounterCell</h1>

<div id="countercell-app" x-data="counterCellApp()" x-init="init()" class="countercell-container">

    <!-- Notice / Instructions -->
    <div class="notice">
        <b>Conseil :</b> Cliquez sur le grand bouton <b>'+'</b> pour incrémenter ou le petit <b>'-'</b> pour décrémenter.
    </div>

    <!-- Preset Selector -->
    <div class="preset-select">
        <template x-for="preset in presets" :key="preset.key">
            <button type="button" :class="{'active': selectedPresetKey === preset.key}" @click="selectPreset(preset.key)" x-text="preset.label"></button>
        </template>
    </div>

    <!-- Main Counter Grid -->
    <div class="counter-grid">
        <template x-for="cell in cells" :key="cell.id">
            <div class="counter-cell" :style="`border-top-color: var(${cell.color})`">
                <div class="cell-info">
                    <span class="cell-name" x-text="cell.name"></span>
                    <span class="cell-count" x-text="cell.count"></span>
                </div>
                <div class="cell-controls">
                    <button class="count-button decrement" @click="decrement(cell.id)" :disabled="cell.count === 0" title="Décrémenter">-</button>
                    <button class="count-button increment" @click="increment(cell.id)" :style="`background-color: var(${cell.color})`" title="Incrémenter">+</button>
                </div>
            </div>
        </template>
    </div>

    <!-- Results Area -->
    <div class="results-area" x-show="totalCount > 0" x-transition>
        <div class="results-header">
            <h2>📊 Résultats</h2>
            <div class="total-count-badge">
                Total: <span x-text="totalCount"></span>
            </div>
        </div>
        <div class="results-list">
            <template x-for="result in results" :key="result.id">
                <div class="result-item" x-show="result.count > 0">
                    <div class="result-label">
                        <span x-text="result.name"></span>
                        <span class="result-raw-count" x-text="`(${result.count})`"></span>
                    </div>
                    <div class="result-bar-container">
                        <progress class="result-progress" :value="result.percentage" max="100" :style="`--progress-color: var(${result.color})`"></progress>
                        <span class="result-percentage" x-text="`${result.percentage.toFixed(1)}%`"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="actions-footer">
        <button class="action-button reset-button" @click="resetCounts()" :disabled="totalCount === 0">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/><path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/></svg>
            Réinitialiser
        </button>
    </div>

    <!-- Placeholder when no counts yet -->
    <template x-if="totalCount === 0">
        <div class="counter-placeholder">
            <p>👇 Le compte est à zéro. Commencez à compter !</p>
        </div>
    </template>
</div>


<script>
    function counterCellApp() {
        return {
            // --- State ---
            presets: [
                {
                    key: 'direct',
                    label: '🔬 Examen Direct',
                    cells: [
                        { id: 'lym', name: 'Lymphocytes', count: 0, color: '--green' },
                        { id: 'pnn', name: 'PNN', count: 0, color: '--blue' },
                    ]
                },
                {
                    key: 'frottis_complet',
                    label: '🩸 Frottis Sanguin',
                    cells: [
                        { id: 'pnn', name: 'PNN', count: 0, color: '--blue' },
                        { id: 'lym', name: 'Lymphocytes', count: 0, color: '--green' },
                        { id: 'mon', name: 'Monocytes', count: 0, color: '--purple' },
                        { id: 'pne', name: 'PNE', count: 0, color: '--orange' },
                        { id: 'pnb', name: 'PNB', count: 0, color: '--red' },
                        { id: 'meta', name: 'Métamyélocytes', count: 0, color: '--aqua' },
                        { id: 'myelo', name: 'Myélocytes', count: 0, color: '--brown' },
                        { id: 'pro', name: 'Promyélocytes', count: 0, color: '--pink' },
                        { id: 'blast', name: 'Blastes', count: 0, color: '--gray' },
                        { id: 'erythro', name: 'Erythroblastes', count: 0, color: '--yellow' },
                    ]
                },
            ],
            selectedPresetKey: 'direct',
            cells: [],

            // --- Init ---
            init() {
                this.selectPreset(this.selectedPresetKey);
            },

            // --- Computed Properties (as getters) ---
            get totalCount() {
                if (!this.cells || this.cells.length === 0) return 0;
                return this.cells.reduce((sum, cell) => sum + cell.count, 0);
            },
            get results() {
                const total = this.totalCount;
                if (total === 0) {
                    return this.cells.map(cell => ({ ...cell, percentage: 0 }));
                }
                return this.cells
                    .map(cell => ({
                        ...cell,
                        percentage: (cell.count / total) * 100
                    }))
                    .sort((a, b) => b.count - a.count);
            },

            // --- Methods ---
            selectPreset(key) {
                const preset = this.presets.find(p => p.key === key);
                if (preset) {
                    this.selectedPresetKey = key;
                    this.cells = JSON.parse(JSON.stringify(preset.cells));
                }
            },
            increment(cellId) {
                const cell = this.cells.find(c => c.id === cellId);
                if (cell) cell.count++;
            },
            decrement(cellId) {
                const cell = this.cells.find(c => c.id === cellId);
                if (cell && cell.count > 0) {
                    cell.count--;
                }
            },
            resetCounts() {
                this.cells.forEach(cell => cell.count = 0);
            }
        }
    }
</script>


<style>
    h1 { text-align: center; }
    .countercell-container { background: var(--bg1); border-radius: 8px; padding: 1rem 1.5rem; max-width: 900px; margin: 0 auto; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    
    .notice { font-size: 0.9em; color: var(--fg4); margin-bottom: 1.5rem; text-align: center; background: var(--bg2); padding: 0.5rem; border-radius: 4px; }
    
    .preset-select { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; justify-content: center; margin-bottom: 2rem; border-bottom: 1px solid var(--bg3); padding-bottom: 1.5rem; }
    .preset-select button { border: none; border-radius: 4px; padding: 0.5em 1.2em; font-size: 1em; font-weight: bold; background: var(--bg3); color: var(--fg2); cursor: pointer; transition: background 0.15s, color 0.15s; outline: none; }
    .preset-select button.active { background: var(--blue); color: var(--bg); }
    .preset-select button:not(.active):hover { background: var(--blue-dim); color: var(--bg); }

    .counter-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 2.5rem; }
    .counter-cell { 
      background: var(--bg2); 
      border-radius: 6px; 
      padding: 1rem; 
      display: flex; 
      align-items: center; 
      justify-content: space-between; 
      border-left: 5px solid transparent; /* Default transparent border */
      border-top: 5px solid; /* Use top border for color now */
      transition: box-shadow 0.2s ease; 
    }
    .counter-cell:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    
    .cell-info { display: flex; flex-direction: column; align-items: flex-start; margin-right: 0.5rem; }
    .cell-name { font-size: 1.1em; font-weight: bold; color: var(--fg1); word-break: break-word; }
    .cell-count { font-size: 1.8em; font-weight: bold; color: var(--fg); line-height: 1; margin-top: 0.25rem; }

    .cell-controls { display: flex; align-items: center; gap: 0.75rem; }
    .count-button { border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: transform 0.1s ease, background-color 0.15s ease; color: var(--bg); font-weight: bold; line-height: 1; user-select: none; }
    .count-button:hover:not(:disabled) { transform: scale(1.08); }
    .count-button:active:not(:disabled) { transform: scale(1.02); }
    
    .count-button.increment { width: 60px; height: 60px; font-size: 2.8em; }
    .count-button.decrement { width: 38px; height: 38px; font-size: 2em; background-color: var(--bg4); color: var(--fg3); padding-bottom: 3px; }
    .count-button.decrement:hover:not(:disabled) { background-color: var(--red); color: var(--bg); }
    .count-button:disabled { background-color: var(--bg3) !important; color: var(--bg4); cursor: not-allowed; transform: none; }

    .results-area { background: var(--bg2); padding: 1rem 1.5rem; border-radius: 6px; margin-bottom: 1.5rem; }
    .results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--bg3); padding-bottom: 0.8rem; }
    .results-header h2 { margin: 0; color: var(--fg1); text-align: left; }
    .total-count-badge { background-color: var(--orange); color: var(--bg); font-weight: bold; padding: 0.3em 0.8em; border-radius: 15px; font-size: 0.9em; }

    .results-list { display: flex; flex-direction: column; gap: 1rem; }
    .result-item { display: grid; grid-template-columns: 200px 1fr; align-items: center; gap: 1rem; }
    .result-label { font-weight: bold; color: var(--fg2); display: flex; align-items: baseline; gap: 0.5rem; word-break: break-word; flex-wrap: wrap; }
    .result-raw-count { font-size: 0.85em; color: var(--fg4); }
    .result-bar-container { display: flex; align-items: center; gap: 0.8rem; }
    .result-progress { -webkit-appearance: none; appearance: none; width: 100%; height: 12px; border: 1px solid var(--bg3); background-color: var(--bg1); border-radius: 6px; overflow: hidden; }
    .result-progress::-webkit-progress-bar { background-color: var(--bg1); border-radius: 6px; }
    .result-progress::-webkit-progress-value { background-color: var(--progress-color, var(--blue)); border-radius: 6px; transition: width 0.3s ease; }
    .result-progress::-moz-progress-bar { background-color: var(--progress-color, var(--blue)); border-radius: 6px; transition: width 0.3s ease; }
    .result-percentage { font-weight: bold; font-size: 0.9em; min-width: 50px; text-align: right; color: var(--fg1); }

    .actions-footer { display: flex; justify-content: center; margin-top: 1rem; }
    .action-button { background: var(--bg3); color: var(--fg2); border: 1px solid var(--bg4); border-radius: 5px; padding: 0.6rem 1.2rem; font-size: 1em; font-weight: bold; cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 0.5rem; }
    .action-button.reset-button:not(:disabled):hover { background-color: var(--red-dim); border-color: var(--red); color: var(--bg); }
    .action-button:disabled { background: var(--bg2); color: var(--fg4); border-color: var(--bg3); cursor: not-allowed; }

    .counter-placeholder { text-align: center; padding: 2.5rem 1rem; color: var(--fg3); background-color: var(--bg2); border-radius: 6px; border: 2px dashed var(--bg3); margin-top: 2rem;}

    /* --- MOBILE OPTIMIZATION for NO SCROLLING --- */
    @media (max-width: 640px) {
        .result-item { grid-template-columns: 1fr; gap: 0.5rem; }
        .result-label { justify-content: space-between; }
        .result-label .result-raw-count { order: 2; }
        h1 {font-size: 1.8em;}

        /* This is the key change: force a 2-column grid on mobile */
        .counter-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        /* Stack elements vertically inside the smaller grid cells */
        .counter-cell {
            flex-direction: column;
            align-items: stretch; /* Make children take full width */
            gap: 0.75rem;
            padding: 0.75rem;
            border-left: 0; /* Remove side border on mobile */
        }

        /* Center the text info */
        .cell-info {
            align-items: center;
            margin-right: 0;
            text-align: center;
        }

        /* Center the buttons */
        .cell-controls {
            justify-content: center;
        }
    }
</style>
