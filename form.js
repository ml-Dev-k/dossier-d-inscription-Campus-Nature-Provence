window.addEventListener('pageshow', function (event) {
  if (event.persisted || performance.getEntriesByType("navigation")[0].type === "back_forward") {
    window.location.reload();
  }
});

$(document).ready(function () {


  $('#fr-travail').on('change', function () {
    const inputDate = $('input[name="date_inscription_france_travail"]');

    if (this.checked) {
      $('#date-inscription').show();
      inputDate.prop("required", true);
      inputDate[0].setCustomValidity('');
      inputDate.on('invalid', function () {
        this.setCustomValidity('Veuillez renseigner la date de votre inscription à France Travail');
      });
      inputDate.on('input', function () {
        this.setCustomValidity('');
      });

    } else {
      $('#date-inscription').hide();
      inputDate.prop("required", false);
      inputDate.off('invalid');
      inputDate.off('input');
    }
  });

  $(function () {
    function formatSecu(val) {
      return val
        .replace(/\D/g, '')               // supprimer tout sauf les chiffres
        .substring(0, 15)                 // max 15 chiffres
        .replace(/(.{1})(.{2})?(.{2})?(.{2})?(.{3})?(.{3})?(.{0,2})?/, function (_, a, b, c, d, e, f, g) {
          return [a, b, c, d, e, f, g].filter(Boolean).join(' ');
        });
    }

    function formatMSA(val) {
      return val
        .replace(/\D/g, '')               // supprimer tout sauf chiffres
        .substring(0, 11)                 // max 11 chiffres
        .replace(/(.{2})(.{2})?(.{3})?(.{0,3})?/, function (_, a, b, c, d) {
          return [a, b, c, d].filter(Boolean).join(' ');
        });
    }

    function formatFranceTravail(val) {
      return val
        .replace(/\D/g, '') // Supprimer les caractères non numériques
        .substring(0, 12)   // Limite à 12 chiffres
        .replace(/(.{3})(.{3})?(.{3})?(.{0,3})?/, function (_, a, b, c, d) {
          return [a, b, c, d].filter(Boolean).join(' ');
        });
    }

    function formatTelephoneFR(val) {
      return val
        .replace(/\D/g, '')               // Supprimer tout ce qui n'est pas chiffre
        .substring(0, 10)                 // Max 10 chiffres
        .replace(/(..)(..)?(..)?(..)?(..)?/, function (_, a, b, c, d, e) {
          return [a, b, c, d, e].filter(Boolean).join(' ');
        });
    }


    $('#secu').on('keyup', function () {
      const cursorPos = this.selectionStart;
      const valAvant = this.value;
      this.value = formatSecu(this.value);

      // Corrige le curseur si nécessaire
      if (this.value.length > valAvant.length) this.setSelectionRange(cursorPos + 1, cursorPos + 1);
    });

    $('#msa').on('keyup', function () {
      const cursorPos = this.selectionStart;
      const valAvant = this.value;
      this.value = formatMSA(this.value);

      if (this.value.length > valAvant.length) this.setSelectionRange(cursorPos + 1, cursorPos + 1);
    });

    $('#id-france-travail').on('keyup', function () {
      const cursorPos = this.selectionStart;
      const valAvant = this.value;
      this.value = formatFranceTravail(this.value);

      if (this.value.length > valAvant.length) this.setSelectionRange(cursorPos + 1, cursorPos + 1);
    });

    $('#tel').on('keyup', function () {
      const cursorPos = this.selectionStart;
      const valAvant = this.value;
      this.value = formatTelephoneFR(this.value);

      if (this.value.length > valAvant.length) {
        this.setSelectionRange(cursorPos + 1, cursorPos + 1);
      }
    });

  });

  $('#financement-region').on('change', function () {
    $('#validation-region').toggle(this.checked);
  });

  $('#autre-finance').on('change', function () {
    const champTexte = $('#input-financement-autre');
    const wrapper = $('#autreFinancement-wrapper');

    if (this.checked) {
      wrapper.show();
      champTexte.prop('required', true);
      champTexte[0].setCustomValidity('');
      champTexte.on('invalid', function () {
        this.setCustomValidity('Veuillez préciser votre type de financement');
      });
      champTexte.on('input', function () {
        this.setCustomValidity('');
      });
    } else {
      champTexte[0].setCustomValidity('');
      wrapper.hide();
      champTexte.prop('required', false);
      champTexte.val('');
      champTexte.off('invalid');
      champTexte.off('input');
    }
  });

  $('#indemnites').on('change', function () {
    $('#type-indem').toggle(this.checked);
  });

  $('#autre-indem').on('change', function () {
    const champTexte = $('#input-indem-autres');
    const wrapper = $('#autreIndem-wrapper');

    if (this.checked) {
      wrapper.show();
      champTexte.prop('required', true);
      champTexte[0].setCustomValidity('');
      champTexte.on('invalid', function () {
        this.setCustomValidity('Veuillez préciser le type d’indemnité');
      });
      champTexte.on('input', function () {
        this.setCustomValidity('');
      });
    } else {
      champTexte[0].setCustomValidity('');
      wrapper.hide();
      champTexte.prop('required', false);
      champTexte.val('');
      champTexte.off('invalid');
      champTexte.off('input');
    }
  });

  $('input[name="validation"]').on('change', function () {
    const isAutre = $('#autre-validation').is(':checked');
    const wrapper = $('#autreValidation-wrapper');
    const champTexte = $('#input-validation-autres');

    if (isAutre) {
      wrapper.show();
      champTexte.prop('required', true);
      champTexte[0].setCustomValidity('');
      champTexte.on('invalid', function () {
        this.setCustomValidity('Veuillez préciser qui a validé le financement');
      });
      champTexte.on('input', function () {
        this.setCustomValidity('');
      });
    } else {
      wrapper.hide();
      champTexte.val('');
      champTexte.prop('required', false);
      champTexte[0].setCustomValidity('');
      champTexte.off('invalid');
      champTexte.off('input');
    }
  });



$.get('https://restcountries.com/v3.1/all?fields=name,translations', function (data) {
  const select = $('#nationalite');
  select.append(`<option value="France">France</option>`);
  const autresPays = data
    .filter(c => c.translations && c.translations.fra && c.translations.fra.common !== "France")
    .sort((a, b) => a.translations.fra.common.localeCompare(b.translations.fra.common, 'fr'));

  autresPays.forEach(country => {
    const nom = country.translations.fra.common;
    select.append(`<option value="${nom}">${nom}</option>`);
  });
});

$(function () {
  $("#adresse_postale").autocomplete({
    source: function (request, response) {
      $.get("https://api-adresse.data.gouv.fr/search/", {
        q: request.term,
        limit: 5
      }, function (data) {
        response($.map(data.features, function (feature) {
          return {
            label: feature.properties.label,
            value: feature.properties.label,
            city: feature.properties.city,
            postcode: feature.properties.postcode
          };
        }));
      });
    },
    select: function (event, ui) {
      $('#code_postal').val(ui.item.postcode);
      $('#ville').val(ui.item.city);
    },
    minLength: 3
  });
});

$(function () {
  $("#lieu_naissance_complet").autocomplete({
    source: function (request, response) {
      $.get("https://api-adresse.data.gouv.fr/search/", {
        q: request.term,
        type: "municipality",
        limit: 10
      }, function (data) {
        response($.map(data.features, function (feature) {
          return {
            label: feature.properties.postcode + ' ' + feature.properties.city,
            value: feature.properties.postcode + ' ' + feature.properties.city,
            city: feature.properties.city,
            postcode: feature.properties.postcode
          };
        }));
      });
    },
    select: function (event, ui) {
      $('#lieu_naissance_cp').val(ui.item.postcode);
      $('#lieu_naissance_ville').val(ui.item.city);
    },
    minLength: 2
  });
});

$('#verso-checkbox').on('change', function () {
  const checked = $(this).is(':checked');

  $('#verso-upload').toggle(checked);
  $('#id-verso').prop('required', checked);
  if (!checked) {
    $('#id-verso').val('');
    $('#id-verso-name').text('Aucun fichier sélectionné');
    $('#id-verso-name').toggle(checked);
  }
});


$('form').on('submit', function (e) {
  if (!navigator.onLine) {
    e.preventDefault();
    $('form').on('submit', function (e) {
      if (!navigator.onLine) {
        e.preventDefault(); // Stoppe le formulaire
        $('#offline-message').fadeIn();
      }
    });
    $('#offline-message').on('click', function (e) {
      if (!$(e.target).closest('.offline-box').length) {
        $('#offline-message').fadeOut();
      }
    });
  } else {
    $('#loading-overlay').fadeIn();
  }
});

const formations = {
  "✔ Métiers du Paysage": [
    "BTS Aménagements Paysagers",
    "BP Aménagements Paysagers",
    "BPAOSP - Brevet Professionnel Agricole (BPA) Ouvrier Spécialisé du Paysage",
    "Bac Professionnel - Aménagements Paysagers",
    "CAPa jardinier paysagiste"
  ],
  "✔ Métiers de l'Environnement": [
    "BTS GPN - BTSA Gestion et Protection de la Nature",
    "Bachelor GPMM"
  ],
  "✔ Métiers du Monde Animal": [
    "ASV - Auxiliaire Spécialisé Vétérinaire",
    "CAPA Maréchal Ferrant",
    "ACACED - Attestation de Connaissances pour les Animaux de Compagnie d’Espèces Domestiques"
  ],
  "✔ Métiers de l'Agriculture et de l'Alimentation": [
    "BP Responsable d'Entreprise Agricole [Apprentissage]",
    "BP Responsable d'Entreprise Agricole [Formation Continue]",
    "Brevet de Technicien Supérieur Agricole (BTSa) Viticulture-Oenologie [Apprentissage]"
  ],
  "✔ Métiers du Service à la Personne": [
    "CAPa SAPVER"
  ],
  "✔ Formations courtes": [
    "Certibiocide/Certiphyto",
    "Formations courtes agriculture",
    "Hygiène Alimentaire",
    "Validation des Acquis de l'Expérience (VAE)"
  ]
};

const horsGroupe = [
  "Autres"
];


const $select = $('#formation');

// Ajout des optgroup
$.each(formations, function (groupe, liste) {
  const $optgroup = $('<optgroup>').attr('label', groupe);
  $.each(liste, function (_, nom) {
    $optgroup.append($('<option>').val(nom).text(nom));
  });
  $select.append($optgroup);
});

// Ajout des options hors groupe
$.each(horsGroupe, function (_, nom) {
  $select.append($('<option>').val(nom).text(nom));
});




});




