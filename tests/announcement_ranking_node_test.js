const assert = require('assert');
const fs = require('fs');
const rank = require('../assets/announcement_ranking.js');
const fixture = JSON.parse(fs.readFileSync(__dirname + '/fixtures/announcement_ranking.json', 'utf8'));
assert.deepStrictEqual(rank(fixture.items).map(item => item.id), fixture.expected);
assert.deepStrictEqual(
    rank(Array.from({ length: 10 }, (_, index) => ({ id: String(10 - index), priority: 10 - index }))).map(item => item.priority),
    [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
);
assert.deepStrictEqual(
    rank([
        { id: 'unknown', priority: 'unknown' },
        { id: 'low', priority: 'low' },
        { id: 'normal', priority: 'normal' },
        { id: 'high', priority: 'high' },
        { id: 'urgent', priority: 'urgent' },
    ]).map(item => item.id),
    ['urgent', 'high', 'normal', 'unknown', 'low']
);
console.log('Announcement JS ranking parity passed');