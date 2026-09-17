# Woo Publication Platform

## Terminology

A list of common used terms in the Woo Publication Platform. Dutch equivalents are given where the UI uses them, since
the code is English and the interface is Dutch.

<dl>
  <dt>balie</dt>
  <dd>the admin interface, served by the <code>admin</code> application and mounted at <code>/balie</code>.</dd>

  <dt>tenant</dt>
  <dd>an organisation the platform is hosted for, each with its own database, search index, styling and translations. One of <code>minvws</code>, <code>minfin</code> or <code>minbuza</code>. Not to be confused with an organisation.</dd>

  <dt>application id</dt>
  <dd>which of the applications an instance runs as: <code>admin</code>, <code>public</code>, <code>publication_api</code>, <code>worker</code> or <code>shared</code>. Replaced the older <code>APP_MODE</code>.</dd>

  <dt>organisation / governing body (bestuursorgaan)</dt>
  <dd>the government body responsible for a publication and the unit a user, dossier, inquiry and subject belongs to within a tenant.
      Most authorization rules are scoped to it.</dd>

  <dt>department</dt>
  <dd>a government department (ministerie). An organisation has one or more, and a dossier is attributed to one or more.</dd>

  <dt>publication / dossier</dt>
  <dd>a published body of information on one topic. "Dossier" is the term used throughout the code; "publication" is thegeneral term used in the interface for publishing information on the platform. Every dossier has a publication type.</dd>

  <dt>publication type / dossier type</dt>
  <dd>what kind of publication a dossier is: WooDecision, Covenant, AnnualReport, InvestigationReport, Disposition,
      ComplaintJudgement, OtherPublication, Advice, RequestForAdvice or DraftDecision.
      These types are also called information categories. The type decides which wizard steps, relations and public pages apply.</dd>

  <dt>Woo-decision (Woo-besluit)</dt>
  <dd>the original and most complex publication type: a decision on whether information from an inquiry is made public, with a production report and a set of documents. It is the only type that relates to inquiries.</dd>

  <dt>covenant (convenant)</dt>
  <dd>a publication type for an agreement between the government and one or more other parties.</dd>

  <dt>annual report or plan (jaarverslag / jaarplan)</dt>
  <dd>a publication type for a year plan, which describes intended activities, or a year report, which describes government actions and results.</dd>

  <dt>investigation report (onderzoeksrapport)</dt>
  <dd>a publication type containing the result of an investigation request.</dd>

  <dt>disposition (beschikking)</dt>
  <dd>a publication type for a decision on a specific situation.</dd>

  <dt>complaint judgement (klachtoordeel)</dt>
  <dd>a publication type containing a response to a complaint.</dd>

  <dt>other publication (overig)</dt>
  <dd>a publication type for a publication that does not fit another type.</dd>

  <dt>advice (advies)</dt>
  <dd>a publication type for advice on drafting laws and regulations or other subjects.</dd>

  <dt>request for advice (adviesaanvraag)</dt>
  <dd>a publication type for a request for advice on drafting laws and regulations or other subjects.</dd>

  <dt>inquiry (zaak)</dt>
  <dd>an information request from a person for the government to make information public. It groups the documents and
      Woo-decisions relevant to that request, contains only the information specifically requested, and gives the requester
      early access to it. Identified by <code>inquiryNumber</code> (zaaknummer).</dd>

  <dt>case</dt>
  <dd>an older synonym for inquiry. Should not be used in the codebase.
      It does survive as an accepted spreadsheet column header: production report and inquiry-link imports
      accept <code>case</code>, <code>casenr</code>, <code>zaaknr</code> and <code>zaaknummer</code>
      alongside <code>inquiry_number</code>.</dd>

  <dt>document</dt>
  <dd>an individual file published as part of a Woo-decision, with its own metadata, judgement and grounds. Only Woo-decisions have documents.</dd>

  <dt>metadata fields (metadata-velden)</dt>
  <dd>fields containing metadata for a document.</dd>

  <dt>DocumentID</dt>
  <dd>the unique document identifier generated in Zylab.</dd>

  <dt>suspended (opgeschort)</dt>
  <dd>a document included in an inventory list but unavailable on the platform, for example because it is disputed in court when published.</dd>

  <dt>withdrawn (ingetrokken)</dt>
  <dd>a document removed because it was not correctly redacted.</dd>

  <dt>main document (hoofddocument)</dt>
  <dd>the single principal file of a publication, for most types the publication itself. Distinct from a document: a Covenant has a main document, not documents.</dd>

  <dt>attachment (bijlage)</dt>
  <dd>a supporting file alongside a main document. For a covenant, this can be an earlier version of the covenant or a related document.
      Its kind is an <code>AttachmentType</code>, whose values are TOOI identifiers.</dd>

  <dt>covenant parties (partijen)</dt>
  <dd>the parties between whom a covenant is made.</dd>

  <dt>decision attachment (besluitsbrief)</dt>
  <dd>an extensive explanation of why and on which grounds a Woo decision was made.</dd>

  <dt>production report (productierapport)</dt>
  <dd>the <code>.xlsx</code> spreadsheet that is uploaded for a Woo-decision, listing the documents and their metadata.
      Processed asynchronously into <code>Document</code> records.</dd>

  <dt>inventory (inventarislijst)</dt>
  <dd>the document list, containing a selection of production-report columns, that the platform generates and offers for download on the public site.
      Derived from the production report. The two are routinely confused: the production report goes in,
      the inventory comes out.</dd>

  <dt>document prefix</dt>
  <dd>the short code that scopes dossier numbers within an organisation. It is not no longer a separate part of a document number.</dd>

  <dt>publication context (publicatiecontext)</dt>
  <dd>the context in which a document is published. A document number is composed of the publication context and document ID,
      in the format <code>{publicationContext}-{documentId}</code>.</dd>

  <dt>subject</dt>
  <dd>an organisation-defined label used to group publications across types.</dd>

  <dt>judgement and grounds</dt>
  <dd>a judgement (beoordeling) determines whether an individual document is partially public, already public, made public,
      not made public, or has nothing found. Grounds (beoordelingsgronden) are the legal exceptions to the Woo for withholding
      information, for example for state security or personal data. Documents that are already public, not made public, or have
      nothing found do not need an uploaded replacement.</dd>

  <dt>ingest</dt>
  <dd>(re-)building all derived data for a publication: indexing into Elasticsearch, extracting content, generating thumbnails. Must be able to restore everything from the database and file storage alone.</dd>

  <dt>Woo-index / DiWoo</dt>
  <dd>the machine-readable sitemap the platform publishes so that other systems can discover its publications, following the DiWoo standard. Uses TOOI value lists for its identifiers.</dd>
</dl>
