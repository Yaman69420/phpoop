# EXAM_NOTES.md

## Editorial Lock

- Bestand: `admin/classes/Admin/Services/LockService.php`

- Methode: 
  - `acquireLock()` - zet een lock op de post
  - `releaseLock()` - haalt de lock weg
  - `isLockedByOther()` - kijkt of iemand anders al bezig is
  - `isLockedByUser()` - checkt of ik zelf de lock heb

- Verantwoordelijkheid van deze laag:
  De Service laag is voor businesslogica. Hier staan de regels van het systeem, 
- zoals wanneer een lock mag worden gezet en wanneer die verloopt. 
- Dit zijn geen database operaties maar beslissingen die het systeem moet maken.

- Wat zou breken bij verplaatsing naar repository:
  Als ik dit in de Repository zou zetten gaat het mis omdat:
  1. Repository is alleen voor data ophalen/opslaan (CRUD), niet voor regels
  2. De lock timeout logica zit dan vast in de database laag, dat hoort daar niet
  3. Testen wordt lasitger want je kan de businesslogica niet los testen
  4. Als je later locks voor iets anders wilt (bv media) moet je alles copy-pasten

---

## Revisies

- Bestand: `admin/classes/Admin/Services/RevisionService.php`

- Methode: `enforceMaxRevisions(int $postId)`

- Hoe werkt de max 3 revisions?
  Dit heb ik ook in de Service gezet want die is ook een regel.
  In `createRevision` roep ik `enforceMaxRevisions` aan.
  Die telt gewoon hoeveel er zijn. Als het er meer dan 3 zijn (mijn constante `MAX_REVISIONS`), 
  haalt hij de oudste eruit.
  Zo blijft de database proper en hebben we er max 3.

---

## Interventie 2: Frontend Isolatie

- De homepage en publieke pgina's mogen niks merken van die locks/revisies.
- Check: ik heb `public/index.php` en `PostsRepository.php` nagekeken.
- Resultaat:
  - `public/index.php` laadt enkle `PostsRepository` en doet niks met LockService of RevisionService.
  - `getPublishedLatest()`: Query is clean, alleen `FROM posts` en een join met media.
  - `getPublishedAll()`: Zelfde verhaal, geen raare joins.
  - `findPublishedBySlug()`: Ook clean.
  - Er wordt nergens gecheckt op `post_locks` of `post_revisions` in de frontend functies.
  - Dus frontend doet 0 extra queries en is volledig onafhankelijk.
  - Eis is behaald, lees-only is gegarandeerd.
