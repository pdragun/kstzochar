# Plan: start location for event invitations (2026-10-07)

Goal: an invitation can say where the event starts (the meeting place, for example "Tesáre, futbalové ihrisko"). The admin picks a location that already exists, adds a new one, or leaves it empty. Locations are stored in their own entity, so the same place is reused instead of being typed again with small differences. A location stores enough address data for Google's `Event` structured data and for a microformats `h-card` inside the `h-event`.

**Status: done in PR #19** (merged 2026-10-08). The schema change became migration `Version20261008190100` in PR #22, so step 6 is superseded: production runs the migrations ([deploy checklist](../deploy-checklist.md)).

**Order: execute this plan before `plan-event-structured-data-2026-10-07.md`.** That plan builds the JSON-LD `location` and the `h-event` `p-location` from the `Location` entity added here. This plan settles its decision 1 and replaces its step 4.

## Decisions (2026-10-07)
1. **Form widget**: a select of existing locations with an empty choice, plus a "new location" sub-form with the address fields. A single text field can't collect a town, address or coordinates for a new location.
2. **Chronicles**: no location. Only `EventInvitation` is linked to `Location`.

## What the consumers need

- **Google (JSON-LD, used by the structured-data plan)**: Google reads only JSON-LD, Microdata and RDFa for event rich results, not microformats. `location` (a `Place`) and `location.address` (a `PostalAddress`) are **required**, and `location.name` is recommended. Google doesn't say which `PostalAddress` fields are required. Its examples use `streetAddress`, `addressLocality`, `postalCode`, `addressRegion` and `addressCountry`. A missing optional field gives at most a warning, which doesn't block the rich result.
- **Microformats (`h-event`)**: `p-location` may be plain text or an embedded `h-card`, `h-adr` or `h-geo`. We use `p-location h-card` with `p-name`, the address properties and the coordinates, so parsers get structured data, not only a name.

## Data model

New entity `App\Entity\Location` (table `location`):

| field | column | required | JSON-LD | h-event (`p-location h-card`) |
|---|---|---|---|---|
| `id` | int, generated | | | |
| `name` | string 255, **unique** | yes | `location.name` | `p-name` |
| `addressLocality` | string 255 | yes | `address.addressLocality` | `p-locality` |
| `addressCountry` | string 2 (ISO 3166-1 alpha-2), default `SK` | yes | `address.addressCountry` | `p-country-name` (name, for example "Slovensko") |
| `streetAddress` | string 255, nullable | no | `address.streetAddress` | `p-street-address` |
| `postalCode` | string 16, nullable | no | `address.postalCode` | `p-postal-code` |
| `addressRegion` | string 255, nullable | no | `address.addressRegion` | `p-region` |
| `latitude` | float, nullable | no | `location.geo.latitude` (`GeoCoordinates`) | `p-latitude` |
| `longitude` | float, nullable | no | `location.geo.longitude` | `p-longitude` |
| `eventInvitations` | one-to-many, inverse side (`mappedBy: 'location'`) | | | |

- Meeting points (a football pitch, a bus stop, a car park under the hill) often have no street address or postal code, so only name, town and country are required. Those three already give a valid `Place`.
- Validation on `Location`:
  - `name`, `addressLocality`: `NotBlank`, `Length(max: 255)`
  - `addressCountry`: `NotBlank`, `Country`
  - `postalCode`: `Length(max: 16)`
  - `latitude`: `Range(-90, 90)`; `longitude`: `Range(-180, 180)`
  - a `Callback`: either both coordinates are set or neither
  - `#[UniqueEntity('name', message: 'location.name_exists')]` ("Toto miesto už existuje, vyber ho zo zoznamu.")
- Name matching: MySQL 8.4 uses `utf8mb4_0900_ai_ci`, which ignores case and accents. The unique index and `UniqueEntity` therefore both treat "tesare, futbalove ihrisko" and "Tesáre, futbalové ihrisko" as the same name, so a near-duplicate gets a form error instead of a second row. The setters trim the strings and collapse repeated spaces.

`EventInvitation` gets:

```php
#[ORM\ManyToOne(targetEntity: Location::class, inversedBy: 'eventInvitations', cascade: ['persist'])]
#[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
private ?Location $location = null;
```

- Nullable, so existing invitations stay without a location and the admin can leave it empty.
- `cascade: ['persist']` means a new location from the form is saved together with the invitation. `EventContentEditor` doesn't need to change: it already persists the invitation, flushes and clears `content.cache`.
- When an invitation is deleted, its location is kept and stays in the list. `onDelete: 'SET NULL'` only matters if a location row is removed by hand.

## Form

In `EventInvitationType`, after `endDate`:

1. `location`: an `EntityType` (`class: Location`, `choice_label: 'name'`, `required: false`, `placeholder: 'form.eventInvitationType.locationNone'` ("— bez miesta —"), `query_builder` ordered by `name`).
2. `newLocation`: a new `App\Form\LocationType` sub-form (`data_class: Location`), **unmapped** (`mapped: false`), `required: false`, `constraints: [new Valid()]`.
   - Fields: `name`, `addressLocality`, `addressCountry` (`CountryType`, `preferred_choices: ['SK', 'CZ', 'PL', 'HU', 'AT']`, data `SK`), `streetAddress`, `postalCode`, `addressRegion`, `latitude`, `longitude` (`NumberType`, `scale: 6`, `html5: true`, `step: any`).
   - `empty_data` is a closure that returns `null` when `name` and `addressLocality` are both empty (`addressCountry` always has a value, so it doesn't count). An untouched sub-form then stays `null`, isn't validated and doesn't create a row. Otherwise it returns a `new Location()`, which the fields are mapped onto.
3. A `FormEvents::SUBMIT` listener on `EventInvitationType`: when `newLocation` has data, it is set as the invitation's `location`, so a filled sub-form wins over the select. Validation errors (missing town, duplicate name, only one coordinate) appear on the sub-form fields, because the sub-form is validated through its own `Valid` constraint.
4. Implementation check: confirm that the `empty_data` closure gives `null` for an untouched sub-form and that its errors show on the sub-form fields. If an unmapped compound child behaves differently, fall back to a small `NewLocationData` DTO with the same fields and constraints, converted to a `Location` in the listener.

Template `templates/event_invitation/createFromEvent.html.twig`:
- Render the form field by field around the location part. The sub-form goes in a `<fieldset>` titled "Nové miesto stretnutia" below the select, with a help text: "Vyplň, len ak miesto nie je v zozname."
- A small progressive enhancement in `assets/js/app.js` (or a Stimulus controller, since `@symfony/stimulus-bridge` is already set up): hide the fieldset behind a "+ pridať nové miesto" button and show it automatically when it contains errors. Without JS the fieldset is just visible.

Translations (`translations/messages.sk.yaml`):
- `form.eventInvitationType.location: 'Miesto stretnutia:'`
- `form.eventInvitationType.locationNone: '— bez miesta —'`
- `form.eventInvitationType.newLocation: 'Nové miesto stretnutia'`, plus a help text
- `form.locationType.*`: `name` ("Názov miesta:"), `addressLocality` ("Obec / mesto:"), `addressCountry` ("Krajina:"), `streetAddress` ("Ulica a číslo:"), `postalCode` ("PSČ:"), `addressRegion` ("Kraj:"), `latitude` ("Zemepisná šírka:"), `longitude` ("Zemepisná dĺžka:"), with help for the coordinates ("napr. 48.557700, z mapy.cz alebo Google Maps")
- validator messages (`location.name_exists`, `location.coordinates_pair`) in `translations/validators.sk.yaml` (create the file if it doesn't exist)
- `templates.eventInvitation.showEventInvitationByYearBySlug.location: 'Miesto stretnutia'`

## Steps

### 1. Entity and repository
- `src/Entity/Location.php` with the fields, constraints and unique constraint described above, getters and setters (the setters normalize whitespace).
- `src/Repository/LocationRepository.php` (`ServiceEntityRepository<Location>`).
- `EventInvitation::$location` with `getLocation()` / `setLocation(?Location)`.

### 2. Form
- `src/Form/LocationType.php` and the changes to `EventInvitationType` (select, sub-form, `SUBMIT` listener).
- Form template, the JS toggle and the translations described above.

### 3. Detail page
- Add `twig/intl-extra` (`task composer -- require twig/intl-extra`; `twig/extra-bundle` is already installed and registers it) for the `country_name` filter. Alternatively, `Location::getCountryName()` could use `Symfony\Component\Intl\Countries::getName($code, 'sk')`, because `symfony/intl` is already installed. Choose while implementing; the filter keeps the entity free of display logic.
- `templates/event_invitation/showEventInvitationByYearBySlug.html.twig`, below the date (inside the existing `h-event` div):
  ```twig
  {% if invitation.location %}
      {% set location = invitation.location %}
      <p id="start-location">{{ 'templates.eventInvitation.showEventInvitationByYearBySlug.location'|trans }}:
          <span class="p-location h-card">
              <strong class="p-name">{{ location.name }}</strong>
              ({% if location.streetAddress %}<span class="p-street-address">{{ location.streetAddress }}</span>, {% endif %}
              {% if location.postalCode %}<span class="p-postal-code">{{ location.postalCode }}</span> {% endif %}
              <span class="p-locality">{{ location.addressLocality }}</span>{% if location.addressRegion %}, <span class="p-region">{{ location.addressRegion }}</span>{% endif %},
              <span class="p-country-name">{{ location.addressCountry|country_name('sk') }}</span>)
              {% if location.latitude is not null %}
                  <data class="p-latitude" value="{{ location.latitude }}"></data>
                  <data class="p-longitude" value="{{ location.longitude }}"></data>
              {% endif %}
          </span>.
      </p>
  {% endif %}
  ```
  The exact visible wording and punctuation can be adjusted. The class names are what parsers read.
- List pages (`showEventInvitationByYear`, upcoming, home page): no change for now.

### 4. Fixtures
- New `LocationFixtures`:
  - "Tesáre, futbalové ihrisko": town "Tesáre", region "Nitriansky kraj", country `SK`, no street, postal code or coordinates. Used by `gulasove-opojenie-v-tesaroch`.
  - "Topoľčany, autobusová stanica": town "Topoľčany", street "Stummerova", postal code "955 01", region "Nitriansky kraj", country `SK`, approximate coordinates (48.5577, 18.1748; fixture data only). Not used by any invitation, to test that unused locations still appear in the select and that the full `h-card` renders after it is picked.
- `EventInvitationFixtures` depends on `LocationFixtures` and sets the location on invitation 1. Invitations 2 and 3 stay without one.

### 5. Tests
Functional tests in `tests/Controller/EventInvitationControllerTest.php`:
- The detail page of `gulasove-opojenie-v-tesaroch` shows `#start-location` with `.p-location.h-card`, `.p-name` "Tesáre, futbalové ihrisko", `.p-locality` "Tesáre", `.p-country-name` "Slovensko", and no `.p-street-address` or `.p-latitude`. Javorový vrch has no `#start-location`.
- The create and edit forms list both fixture locations in the select, with the "— bez miesta —" choice first.
- Picking an existing location in the select links it; the `location` row count stays the same.
- Filling the sub-form (name, town, country) creates one new `Location` and links it, even when the select also has a value (the sub-form wins).
- Leaving both empty: `location` stays null. Choosing "— bez miesta —" on edit removes the link, and the location still exists.
- Sub-form with a name but no town: status 422, an error on the town field, nothing saved.
- Sub-form with a name that exists with different case and accents ("topolcany, autobusova stanica"): 422 with the "už existuje" error.
- Only one coordinate: 422 with the coordinate-pair error.
- After picking the Topoľčany location, the detail page has `.p-street-address`, `.p-postal-code`, `.p-region`, and `data.p-latitude` / `data.p-longitude` with the values.
- Deleting an invitation keeps its location.

Unit test `tests/Entity/LocationTest.php` (or a validator test with `Validation::createValidator()`): whitespace normalization in the setters and the coordinate-pair callback.

Check that the new tests fail before the change, then run `task check` (phpstan, rector:check, phpunit).

### 6. Schema and deployment
- The project has no migrations, so dev and test use `doctrine:schema:create` (`task dbr`, `task test`) and pick the change up automatically.
- Production: before deploying, generate the SQL with `bin/console doctrine:schema:update --dump-sql` against a copy of the production schema and run it. Use the generated statements, because they contain the real index names. Expected shape:
  ```sql
  CREATE TABLE location (
      id INT AUTO_INCREMENT NOT NULL,
      name VARCHAR(255) NOT NULL,
      address_locality VARCHAR(255) NOT NULL,
      address_country VARCHAR(2) NOT NULL,
      street_address VARCHAR(255) DEFAULT NULL,
      postal_code VARCHAR(16) DEFAULT NULL,
      address_region VARCHAR(255) DEFAULT NULL,
      latitude DOUBLE PRECISION DEFAULT NULL,
      longitude DOUBLE PRECISION DEFAULT NULL,
      UNIQUE INDEX UNIQ_… (name),
      PRIMARY KEY (id)
  ) DEFAULT CHARACTER SET utf8mb4;
  ALTER TABLE event_invitation ADD location_id INT DEFAULT NULL;
  ALTER TABLE event_invitation ADD CONSTRAINT FK_… FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE SET NULL;
  CREATE INDEX IDX_… ON event_invitation (location_id);
  ```
- If `twig/intl-extra` is added, `composer.lock` changes, so the production deploy runs `composer install` as usual.
- Update CLAUDE.md (domain model: an invitation has an optional `Location` with an address and coordinates, picked from a select or created through the "new location" sub-form; chronicles have none).

### 7. Delivery
- One PR, branch `feature/invitation-location`, same workflow as the earlier PRs. Merge it before starting the structured-data plan.

## Later
- **An admin page for locations** (list, edit, merge duplicates, delete unused). Done in PR #23 (`/miesta-stretnutia`). With address data and coordinates, fixing a typo matters more than with a name only. Until this page exists, a wrong location can only be fixed in the database or replaced by a new one. Consider doing it soon after this PR.
- A map link on the detail page when coordinates are set (mapy.cz or OpenStreetMap). Open.
- Pre-fill the location of a new invitation from the club's previous invitation for the same event or route. Open.
