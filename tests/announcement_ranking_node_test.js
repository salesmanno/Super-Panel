const assert = require('assert');
const fs = require('fs');
const rank = require('../assets/announcement_ranking.js');
const fixture = JSON.parse(fs.readFileSync(__dirname + '/fixtures/announcement_ranking.json', 'utf8'));
assert.deepStrictEqual(rank(fixture.items, fixture.priority).map(item => item.id), fixture.expected);
console.log('Announcement JS ranking parity passed');