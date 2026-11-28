// Fills the demo database with a lived-in bag for the character «Tester»: gear of several types and grades, resources,
// elixirs, scrolls, recipes, soulshots and pet things.  Used by shop_test.mjs and by hand:
//   node seed.mjs            (BASE=http://127.0.0.1:8080 picks another server)
import { sql, BASE } from './lib.mjs';

const Q = (s) => s.replace(/\s+/g, ' ').trim();

export async function seedBag(base = BASE, usr = 'Tester') {
  const out = [];
  const run = async (q) => { const r = await sql(Q(q), base); out.push(r); return r; };
  // gear: copies of shop rows (the demo shop has every type and grade), image = 'not' means «in the bag»
  await run(`INSERT INTO item (usr,tip,ruka,name,cena,patt,matt,pdef,mdef,soul,spirit,num,nlvl,image)
             SELECT '${usr}',tip,tip2,name,cena,patt,matt,pdef,mdef,soul,spirit,0,nlvl,'not' FROM shop
             WHERE city != '' AND tip = 'weapon' AND nlvl IN (0,20,40) ORDER BY nlvl DESC, cena LIMIT 14`);
  for (const tip of ['golova', 'body', 'pants', 'nogi', 'ruki', 'shit', 'plash', 'amulet', 'kolco', 'rkolco', 'poyas', 'rpoyas']) {
    await run(`INSERT INTO item (usr,tip,ruka,name,cena,patt,matt,pdef,mdef,soul,spirit,num,nlvl,image)
               SELECT '${usr}',tip,tip2,name,cena,patt,matt,pdef,mdef,soul,spirit,0,nlvl,'not' FROM shop
               WHERE city != '' AND tip = '${tip}' AND nlvl IN (0,20,40) ORDER BY nlvl DESC, cena LIMIT 3`);
  }
  // the demo shop sells no trousers / cloaks: two hand-made ones
  await run(`INSERT INTO item (usr,tip,ruka,name,cena,patt,matt,pdef,mdef,soul,spirit,num,nlvl,image)
             VALUES ('${usr}','pants',NULL,'Leather Pants',1200,0,0,40,20,0,0,0,0,'not'),
                    ('${usr}','plash',NULL,'Cloak of Protection',3200,0,0,18,40,0,0,0,20,'not')`);
  // resources, elixirs, scrolls, recipes
  const res = [
    ['Animal Bone', 'bone', 'res', 41], ['Iron Ore', 'iron_ore', 'res', 12], ['Charcoal', 'charcoal', 'res', 250], ['Stem', 'stem', 'res', 7],
    ['Thread', 'thread', 'res', 19], ['Leather', 'leather', 'res', 33], ['Coarse Bone Powder', 'bone_powder', 'res', 5], ['Varnish', 'varnish', 'res', 3],
    ['Cokes', 'cokes', 'res', 9], ['Mithril Ore', 'mithril_ore', 'res', 2], ['Silver Nugget', 'silver', 'res', 11], ['Oriharukon Ore', 'oriharukon', 'res', 1],
    ['Healing Potion', 'heal_potion', 'elexir', 15], ['Mana Potion', 'mana_potion', 'elexir', 8], ['Elixir of Life', 'elixir_life', 'elexir', 3],
    ['Scroll: Enchant Weapon', 'sc_ench_w', 'scroll', 2], ['Scroll of Escape', 'sc_escape', 'scroll', 6],
    ['Рецепт: Short Bow', 'rec_short_bow', 'rec', 1], ['Рецепт: Mace', 'rec_mace', 'rec', 2],
  ];
  for (const [name, lat, tip, kol] of res) {
    await run(`INSERT INTO res (usr,name,lat_name,tip,what,give,kol,cena) VALUES ('${usr}','${name}','${lat}','${tip}','','',${kol},0)`);
  }
  await run(`INSERT INTO res (usr,name,lat_name,tip,what,give,kol,cena) VALUES ('${usr}','Fish','fish','res','','',4,0)`);
  // soulshots and pet things
  await run(`INSERT INTO soulshots (usr,kol,status,nlvl) VALUES ('${usr}',1500,0,0)`);
  await run(`INSERT INTO soulshots (usr,kol,status,nlvl) VALUES ('${usr}',300,1,20)`);
  const pits = [['weapon', 'Fang Claws', 12, 0, 0, 'wolf', 0], ['weapon', 'Steel Claws', 30, 0, 0, 'not', 20], ['golova', 'Wolf Helm', 0, 6, 40, 'wolf', 0],
    ['body', 'Chain Barding', 0, 14, 90, 'dog', 20], ['nogi', 'Paw Guards', 0, 4, 20, 'not', 0]];
  for (const [tip, name, sila, prot, hp, rasa, nlvl] of pits) {
    await run(`INSERT INTO pit_item (usr,pit,tip,name,cena,prot,hp,sila,rasa,nlvl,image) VALUES ('${usr}',0,'${tip}','${name}',100,${prot},${hp},${sila},'${rasa}',${nlvl},'not')`);
  }
  return out;
}

if (import.meta.url === `file://${process.argv[1]}`) {
  const r = await seedBag();
  const bad = r.filter((x) => x && x.error);
  console.log('seeded', r.length, 'statements;', bad.length ? 'errors: ' + JSON.stringify(bad.slice(0, 3)) : 'no errors');
  const c = await sql(`SELECT tip, COUNT(*) FROM item WHERE usr='Tester' GROUP BY tip`);
  console.log(JSON.stringify(c.rows));
}
