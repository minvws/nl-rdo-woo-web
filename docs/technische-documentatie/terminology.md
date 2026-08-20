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

  <dt>organisation</dt>
  <dd>the unit a user, dossier, inquiry and subject belongs to, within a tenant. Most authorization rules are scoped to it.</dd>

  <dt>department</dt>
  <dd>a government department (ministerie). An organisation has one or more, and a dossier is attributed to one or more.</dd>

  <dt>publication / dossier</dt>
  <dd>a published body of information on one topic. "Dossier" is the term used throughout the code; "publication" is the term used in the interface. Every dossier has a publication type.</dd>

  <dt>publication type / dossier type</dt>
  <dd>what kind of publication a dossier is: WooDecision, Covenant, AnnualReport, InvestigationReport, Disposition,
      ComplaintJudgement, OtherPublication, Advice, RequestForAdvice or DraftDecision.
      The type decides which wizard steps, relations and public pages apply.</dd>

  <dt>Woo-decision (Woo-besluit)</dt>
  <dd>the original and most complex publication type: a decision on an information request, with a production report and a set of documents. It is the only type that relates to inquiries.</dd>

  <dt>inquiry (zaak)</dt>
  <dd>an information request. Groups the documents and Woo-decisions relevant to one request, and gives the requester early access to them. Identified by <code>inquiryNumber</code> (zaaknummer).</dd>

  <dt>case</dt>
  <dd>an older synonym for inquiry. Should not be used in the codebase.
      It does survive as an accepted spreadsheet column header: production report and inquiry-link imports
      accept <code>case</code>, <code>casenr</code>, <code>zaaknr</code> and <code>zaaknummer</code>
      alongside <code>inquiry_number</code>.</dd>

  <dt>document</dt>
  <dd>an individual file published as part of a Woo-decision, with its own metadata, judgement and grounds. Only Woo-decisions have documents.</dd>

  <dt>main document (hoofddocument)</dt>
  <dd>the single principal file of a publication, for most types the publication itself. Distinct from a document: a Covenant has a main document, not documents.</dd>

  <dt>attachment (bijlage)</dt>
  <dd>a supporting file alongside a main document. Its kind is an <code>AttachmentType</code>, whose values are TOOI identifiers.</dd>

  <dt>production report (productierapport)</dt>
  <dd>the spreadsheet that is uploaded for a Woo-decision, listing the documents and their metadata.
      Processed asynchronously into <code>Document</code> records.</dd>

  <dt>inventory (inventarislijst)</dt>
  <dd>the document list that the platform generates and offers for download on the public site.
      Derived from the production report. The two are routinely confused: the production report goes in,
      the inventory comes out.</dd>

  <dt>document prefix</dt>
  <dd>the short code that scopes dossier and document numbers within an organisation, for example the <code>VWS</code> in <code>VWS-534-3444</code>.</dd>

  <dt>subject</dt>
  <dd>an organisation-defined label used to group publications across types.</dd>

  <dt>judgement and grounds</dt>
  <dd>whether a document is public, partially public or not public, and the legal grounds for withholding any part of it.</dd>

  <dt>ingest</dt>
  <dd>(re-)building all derived data for a publication: indexing into Elasticsearch, extracting content, generating thumbnails. Must be able to restore everything from the database and file storage alone.</dd>

  <dt>Woo-index / DiWoo</dt>
  <dd>the machine-readable sitemap the platform publishes so that other systems can discover its publications, following the DiWoo standard. Uses TOOI value lists for its identifiers.</dd>
</dl>
