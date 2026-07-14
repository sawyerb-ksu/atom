<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \QubitOai
 */
class QubitOaiTest extends TestCase
{
    public function testLoadXmlDoesNotResolveExternalEntities()
    {
        // OAI XML parsing must not resolve external entity content into the DOM.
        $secretFile = tempnam(sys_get_temp_dir(), 'atom-oai-xxe-secret-');
        file_put_contents($secretFile, 'ATOM-OAI-XXE-SHOULD-NOT-APPEAR');

        $xml = sprintf(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE OAI-PMH [
  <!ENTITY xxe SYSTEM "file://%s">
]>
<OAI-PMH>
  <responseDate>&xxe;</responseDate>
</OAI-PMH>
XML,
            $secretFile
        );

        try {
            $doc = QubitOai::loadXML($xml);

            $this->assertStringNotContainsString(
                'ATOM-OAI-XXE-SHOULD-NOT-APPEAR',
                $doc->saveXML(),
                'External entity contents must not be resolved into OAI XML.'
            );
        } finally {
            @unlink($secretFile);
        }
    }
}
