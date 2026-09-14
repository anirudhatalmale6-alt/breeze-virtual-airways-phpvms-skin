# CSV import pack — Breeze Virtual Airways

Ready-to-upload CSVs for the built-in phpVMS importer. Every file here was imported into a
real phpVMS 7 install before being handed over, so the headers and the column order are the
ones phpVMS actually wants — not a guess.

Upload them in the phpVMS admin: **Aircraft / Flights / Airports / Subfleets → Import from CSV**.

## Upload them in this order

| # | File | Admin page |
|---|------|-----------|
| 0 | `0-airports.csv` | Airports |
| 1 | `1-subfleets.csv` | Subfleets |
| 2 | `2-aircraft.csv` | Aircraft |
| 3 | `3-flights.csv` | Flights |

The order is not optional. Three things go wrong if it isn't followed:

**Flights before airports → the whole import dies.** If the `distance` column is left blank,
phpVMS works the distance out itself by looking both airports up in the database. If either one
isn't there yet it throws `ModelNotFoundException: No query results for model [App\Models\Airport]`
and *no rows at all* get imported. Load the airports first and phpVMS fills the distances in for
you (KCHS–KHPN came out at 577.12 nm on the test import).

**Aircraft before subfleets → nothing to attach them to.** The `subfleet` column on each aircraft
row has to match a `type` that already exists.

**Empty `ranks` column on a subfleet → the aircraft exist but nobody can see them.** The Schedules
page comes up completely blank, with no error to tell you why. The `ranks` column takes rank IDs
separated by semicolons — `1;2;3;4` gives every rank access, which is what's in the file here.

## Careful with the "delete existing" option

The importer has an option to delete what's already there before it loads the file. With it
ticked, an **airports** import runs a `TRUNCATE` — every airport row is gone, including ones your
file doesn't mention. Any flight still pointing at a deleted airport then makes the Schedules page
throw a 500 (`Attempt to read property "name" on null`). Verified, then repaired by re-importing a
file containing *every* airport.

So: leave it unticked to add and update, or if you do tick it, make sure the file contains
everything you want to keep.

## What's in the files

- **`0-airports.csv`** — 12 airports, coordinates and timezones pulled from VaCentral, the same
  source phpVMS's own airport lookup uses. KCHS, KTPA and KPVU are flagged as hubs. Any airport a
  route touches has to be in here.
- **`1-subfleets.csv`** — one subfleet, `A223` / Airbus A220-300, SimBrief type `BCS3`, all four
  ranks granted.
- **`2-aircraft.csv`** — five A220-300s. **The registrations follow the N2xxBZ pattern but are not
  verified against the real fleet** — replace them with the tails you want.
- **`3-flights.csv`** — eight example routes between real Breeze cities, as a shape to copy. Block
  times are estimates. `flight_time` is in **minutes** and is required; leave `distance` blank and
  phpVMS calculates it.

## Airline codes used

`MXY` / `MX`, callsign `BREEZE`. Change them in the CSV if you want different ones — the `airline`
column on subfleets and flights has to match the airline's ICAO code in phpVMS.
