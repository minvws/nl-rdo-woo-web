window.setInquiryDossierLink = () => {
    var caseNumberSelect = document.getElementById('dossier-case-nr');
    var caseNumber = caseNumberSelect.options[caseNumberSelect.selectedIndex].text;

    var link = document.getElementById('dossier-case-link')
    link.setAttribute('href', link.dataset.base_href + caseNumber);
}
