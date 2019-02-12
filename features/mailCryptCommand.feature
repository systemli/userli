Feature: MailCryptCommand

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email                    | password | mailCrypt | mailCryptSecretBox | mailCryptPublicKey |
      | mailcrypt@example.org    | password | 1         | exEBoGZ040c/14eWwmHAM/loVqDj+GVkb+e75k+MU1jgoYA7Dw65IUj4dilGk1a2dyMTGSSHZcpezOMD1x5ZkHY/4yy5DN98gTmKkm77CNhOF02HN1dJGcq00icFVa6j9KoFXGHo02RTwEu5AWiWAy/QrcqWF+g7YvBUBEG9Hh+AdQ7uzAlW+iEOx7uOsWG2bU0fIRF0UCnMUaK+Cfv/ETs3Mfeed+qEcAxKTLeKXC+YWEDt7bPo+kNEwGFfBOr7XaS+OZt+nUv0gI/2H3oasQS1mtSGY77bqZmsfYbSEwZ4H2AqaB/WI8hywC0h3QU6/1GkX76yD85Dt+cvNmVrpQWPMgc2w5ley2Il2SJFTi4ohtx3PKw+2DDvqH7tlPFTR8PtM9Mv5ArvDhFEez7OnoLe0beHWNWEhzjyOskqXH/nMl59itCh4JLRDHjwixkPLpW4O+zOyKkh7BCR52JA62nSVUAXFQS35xfRPcwaZ3x7iwTBvDs7RE6DdooERZkKb2Xm8NZK+hIBVt17/LWnOF7q09SrKe4fl41iwuEsLEV71ICkZN3dVUSHagqNNHuQ+PrW+pSJf5h2j0YE1zGv98vMcYwMTi9P3a0gvyNp9w48HOZuZDH3jiUsQWZ1EUTGGd1nT56ysvm8MyyanoV8eM34qBd8snHgiWsbY4dmReW4kEXGbeqUkEVzx+Y3XUMPrmSO4VmKAXrwsmvJ1PhEvd6Yp2LQTXzZG3wNNZpS9bQkPGm8wGW5vg== | LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlHYk1CQUdCeXFHU000OUFnRUdCU3VCQkFBakE0R0dBQVFBU09BbXlsNzdGVGdnZ05nZEN0QldBRFdBU2s0WApVckx0RnpWbjBocUZDaExjYUt3Y0VwZmI4RDlPeGFSZHJFM3ZiVmFXd1NNUEZUNHkweVhFdGRqSHZzb0JXVkp5ClVSN25td0t2dEFEWkNmYXpNQW5hS2N3YkpkalBIa0JNT0V1OGpUSnd2bVd1OEV0UHBJU29sdE02K2xmN2N3RTUKNGZaaVIzQ0d2YnRvaVJjVXFsRT0KLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0tCg== |
      | nomailcrypt1@example.org | password | 0         | exEBoGZ040c/14eWwmHAM/loVqDj+GVkb+e75k+MU1jgoYA7Dw65IUj4dilGk1a2dyMTGSSHZcpezOMD1x5ZkHY/4yy5DN98gTmKkm77CNhOF02HN1dJGcq00icFVa6j9KoFXGHo02RTwEu5AWiWAy/QrcqWF+g7YvBUBEG9Hh+AdQ7uzAlW+iEOx7uOsWG2bU0fIRF0UCnMUaK+Cfv/ETs3Mfeed+qEcAxKTLeKXC+YWEDt7bPo+kNEwGFfBOr7XaS+OZt+nUv0gI/2H3oasQS1mtSGY77bqZmsfYbSEwZ4H2AqaB/WI8hywC0h3QU6/1GkX76yD85Dt+cvNmVrpQWPMgc2w5ley2Il2SJFTi4ohtx3PKw+2DDvqH7tlPFTR8PtM9Mv5ArvDhFEez7OnoLe0beHWNWEhzjyOskqXH/nMl59itCh4JLRDHjwixkPLpW4O+zOyKkh7BCR52JA62nSVUAXFQS35xfRPcwaZ3x7iwTBvDs7RE6DdooERZkKb2Xm8NZK+hIBVt17/LWnOF7q09SrKe4fl41iwuEsLEV71ICkZN3dVUSHagqNNHuQ+PrW+pSJf5h2j0YE1zGv98vMcYwMTi9P3a0gvyNp9w48HOZuZDH3jiUsQWZ1EUTGGd1nT56ysvm8MyyanoV8eM34qBd8snHgiWsbY4dmReW4kEXGbeqUkEVzx+Y3XUMPrmSO4VmKAXrwsmvJ1PhEvd6Yp2LQTXzZG3wNNZpS9bQkPGm8wGW5vg== | LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlHYk1CQUdCeXFHU000OUFnRUdCU3VCQkFBakE0R0dBQVFBU09BbXlsNzdGVGdnZ05nZEN0QldBRFdBU2s0WApVckx0RnpWbjBocUZDaExjYUt3Y0VwZmI4RDlPeGFSZHJFM3ZiVmFXd1NNUEZUNHkweVhFdGRqSHZzb0JXVkp5ClVSN25td0t2dEFEWkNmYXpNQW5hS2N3YkpkalBIa0JNT0V1OGpUSnd2bVd1OEV0UHBJU29sdE02K2xmN2N3RTUKNGZaaVIzQ0d2YnRvaVJjVXFsRT0KLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0tCg== |
      | nomailcrypt2@example.org | password | 1         |                    |                    |

  @mailCryptCommand
  Scenario: Check if mailCrypt arguments are passed when set
    When I run console command "usrmgmt:users:mailcrypt mailcrypt@example.org password"
    Then I should see "LS0tLS1CRUdJTiBQUklWQVRFIEtFWS0tLS0tCk1JSHVBZ0VBTUJBR0J5cUdTTTQ5QWdFR0JTdUJCQUFqQklIV01JSFRBZ0VCQkVJQWE0cVIxUGl1ZGZsazgzSDQKN0lXdG5zdE80QjNaQ0tkVWhGTTBBZXpLcUc2KzZPMXR3cklHL2preXYwZm81ZTZQWDBtVUtXSHY2OGJMZ1FKNQo3UUIrYmwyaGdZa0RnWVlBQkFCSTRDYktYdnNWT0NDQTJCMEswRllBTllCS1RoZFNzdTBYTldmU0dvVUtFdHhvCnJCd1NsOXZ3UDA3RnBGMnNUZTl0VnBiQkl3OFZQakxUSmNTMTJNZSt5Z0ZaVW5KUkh1ZWJBcSswQU5rSjlyTXcKQ2RvcHpCc2wyTThlUUV3NFM3eU5NbkMrWmE3d1MwK2toS2lXMHpyNlYvdHpBVG5oOW1KSGNJYTl1MmlKRnhTcQpVUT09Ci0tLS0tRU5EIFBSSVZBVEUgS0VZLS0tLS0K\nLS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlHYk1CQUdCeXFHU000OUFnRUdCU3VCQkFBakE0R0dBQVFBU09BbXlsNzdGVGdnZ05nZEN0QldBRFdBU2s0WApVckx0RnpWbjBocUZDaExjYUt3Y0VwZmI4RDlPeGFSZHJFM3ZiVmFXd1NNUEZUNHkweVhFdGRqSHZzb0JXVkp5ClVSN25td0t2dEFEWkNmYXpNQW5hS2N3YkpkalBIa0JNT0V1OGpUSnd2bVd1OEV0UHBJU29sdE02K2xmN2N3RTUKNGZaaVIzQ0d2YnRvaVJjVXFsRT0KLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0tCg==" in the console output

  Scenario: Check if public MailCrypt key is passed when set
    When I run console command "usrmgmt:users:mailcrypt mailcrypt@example.org"
    Then I should see "LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlHYk1CQUdCeXFHU000OUFnRUdCU3VCQkFBakE0R0dBQVFBU09BbXlsNzdGVGdnZ05nZEN0QldBRFdBU2s0WApVckx0RnpWbjBocUZDaExjYUt3Y0VwZmI4RDlPeGFSZHJFM3ZiVmFXd1NNUEZUNHkweVhFdGRqSHZzb0JXVkp5ClVSN25td0t2dEFEWkNmYXpNQW5hS2N3YkpkalBIa0JNT0V1OGpUSnd2bVd1OEV0UHBJU29sdE02K2xmN2N3RTUKNGZaaVIzQ0d2YnRvaVJjVXFsRT0KLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0tCg==" in the console output

  Scenario: Check if mailCrypt arguments are empty when disabled
    When I run console command "usrmgmt:users:mailcrypt nomailcrypt1@example.org password"
    Then I should see empty console output

  Scenario: Check if mailCrypt arguments are empty when unset
    When I run console command "usrmgmt:users:mailcrypt nomailcrypt2@example.org password"
    Then I should see empty console output
