<div style="display: none;" href='/' id="metadata">Iheb Chagra</div>
<div>
  <h1>Iheb Chagra</h1>
  <div x-data="typewriter()" class="typewriter-container">
    <span x-text="displayText" class="typed-text"></span>
    <span class="cursor">⎸</span>
  </div>
</div>
<h2>À Propos</h2>
<p><span x-init x-text="getGreeting()">Bonjour</span>! Je suis <b>Iheb Chagra</b>, médecin résident en microbiologie. Bienvenue sur mon site web personnel. Il contient plusieurs outils pour la pratique médicale. J'espère qu'il vous sera utile.</p>
<h2>Offrez-moi un café ! ☕</h2>
<p>Bien que ce site soit gratuit à tous et sans publicités, sa maintenance et son développement sont entièrement à ma charge, en temps et en argent. Si vous souhaitez soutenir mon travail, votre contribution serait grandement appréciée. <a href="https://ba9chich.com/fr/ihebchagra"><b>Faire un don sur ce lien</b></a></p>
<h2>Mes Projets</h2>
<h3>💊 <a hx-get="/medicasearch" hx-target="#content" hx-swap="outerHTML" hx-select="#content">Médicasearch</a></h3>
<p>Un moteur de recherche des médicaments disponibles en tunisie avec toutes leurs informations.</p>
<h3>🦠 <a hx-get="/casfmsearch" hx-target="#content" hx-swap="outerHTML" hx-select="#content">CA-SFM Search</a></h3>
<p>Un moteur de recherche pour accéder rapidement aux dernières recommendation du CA-SFM.</p>
<h3>🧪 <a hx-get="/apicalcul" hx-target="#content" hx-swap="outerHTML" hx-select="#content">APIcalcul</a></h3>
<p>Un Calculateur de probabilité des résultats des tests API.</p>
<h3>🩸 <a hx-get="/prelevements" hx-target="#content" hx-swap="outerHTML" hx-select="#content">Guide de Prélèvements</a></h3>
<p>Savoir la méthode de prélèvement, la quantité du prélèvement et le prix rapidement.</p>
<h3>⚕️ <a href="https://promety.tn">Promety</a></h3>
<p>Le Guide du Survivant, Survicalls, Survitools. Un projet révolutionnaire crée par l'association <b>AMENA</b>.</p>
<!-- <h3>🚑 <a href="https://premiersecours.tn">premiersecours.tn</a></h3> -->
<!-- <p>Un site complémentaire aux formations du Programme national de formation aux premiers secours en milieu scolaire.Direction par <b>Dr Mylène Ben Hamida</b> et Design du site par <b>Dr Mohamed Aziz Berriche</b>.</p> -->
<h3>🎓 <a href="https://ecn.iheb.tn/">ECN tools</a> <span class='deprecated'>(N'est plus maintenu)</span></h3>
<p> Polysearch ECN, Sériesearch et ECN 3al tayer. Des outils pour la préparation à l'épreuve de résidanat. <b>Dernière mise à jour mars 2024</b>.</p>
<h3>📕 <a href="https://ihebchagra.github.io/polysearch">Polysearch FMT</a> <span class='deprecated'>(N'est plus maintenu)</span></h3>
<p> Moteur de recherche des polycopiés de la faculté de médecine de Tunis.</p>
<h3>❓ <a href="https://quiz.iheb.tn">Quiz Bi-hebdomadaire</a> <span class='deprecated'>(N'est plus maintenu)</span></h3>
<p>Un Quiz d'images des cas cliniques rares publiés.</p>
<h3>🐱 <a href="https://ihebchagra.github.io/medicavet">Médicavet</a> <span class='deprecated'>(N'est plus maintenu)</span></h3>
<p>Un moteur de recherche des médicaments vétérinaires disponibles en tunisie avec toutes leurs informations.</p>
<h3>📚 <a href="https://annexe.iheb.tn">Annexe</a> <span class='deprecated'>(N'est plus maintenu)</span></h3>
<p>une liste de documents utiles pour toutes les spécialités. Collection compilée par Dr Hazem Al Abed.</p>
<h3>📜 <a href="https://ihebchagra.github.io/diagdiscuss">Diagdiscuss</a> <span class='deprecated'>(N'est plus maintenu)</span></h3>
<p>Générateur de discussions diagnostiques.</p>
<h3>💥 <a href="https://ihebchagra.github.io/pharmacoteract">Pharmacoteract</a> <span class='deprecated'>(N'est plus maintenu)</span></h3>
<p>Détecteur d'intéractions médicamenteuses.</p>
<h2>Contactez-moi</h2>
<p>Je suis disponible pour discuter n'importe quel sujet.</p>
<ul>
  <li>E-mail : <a href="mailto:ihebchagra@gmail.com">ihebchagra@gmail.com</a></li>
  <li>Github : <a href="https://github.com/ihebchagra">@ihebchagra</a></li>
  <li>Facebook : <a href="https://www.facebook.com/iheb.chagra">Iheb Chagra</a></li>
  <li>Instagram : <a href="https://www.instagram.com/ihebchagra">@ihebchagra</a></li>
</ul>



<script>
function getGreeting() {
  const currentHour = new Date().getHours();

  if (currentHour >= 18 || currentHour < 5) {
    return 'Bonsoir';
  } else {
    return 'Bonjour';
  }
}

function typewriter() {
  return {
    jobs: [
      "Médecin résident en microbiologie",
      "Programmeur du dimanche",
      "Responsable web/app @ AMENA",
    ],
    displayText: '',
    currentJob: 0,
    charIndex: 0,
    isDeleting: false,
    typeDelay: 100,
    deleteDelay: 50,
    pauseDelay: 1500,

    init() {
      this.typeNextChar();
    },

    typeNextChar() {
      const currentText = this.jobs[this.currentJob];

      if (!this.isDeleting) {
        // Typing
        this.displayText = currentText.substring(0, this.charIndex + 1);
        this.charIndex++;

        // If completed typing
        if (this.charIndex >= currentText.length) {
          this.isDeleting = false;
          // Wait before starting to delete
          setTimeout(() => {
            this.isDeleting = true;
            this.typeNextChar();
          }, this.pauseDelay);
          return;
        }
      } else {
        // Deleting
        this.displayText = currentText.substring(0, this.charIndex - 1);
        this.charIndex--;

        // If completed deleting
        if (this.charIndex <= 0) {
          this.isDeleting = false;
          this.currentJob = (this.currentJob + 1) % this.jobs.length;
        }
      }
      const delay = this.isDeleting ? this.deleteDelay : this.typeDelay;
      setTimeout(() => this.typeNextChar(), delay);
    }
  }
}
</script>

<style>
.typewriter-container {
  font-size: 1em;
  color: var(--fg1);
  font-weight: bold;
  display: flex;
  align-items: center;
  height: 1.5em;
  margin-bottom: 1em;
  font-size: 1.3rem;
}

.cursor {
  display: inline-block;
  animation: blink-cursor 0.8s step-end infinite;
  color: var(--orange);
}

@keyframes blink-cursor {

from,
to {
  opacity: 1;
}

50% {
  opacity: 0;
}
}

.deprecated {
  color: var(--aqua);
}
</style>
