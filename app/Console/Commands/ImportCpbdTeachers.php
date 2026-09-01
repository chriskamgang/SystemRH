<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportCpbdTeachers extends Command
{
    protected $signature = 'cpbd:import-teachers';
    protected $description = 'Importer les enseignants du CPBD dans SystemRH (company_id=2)';

    protected array $teachers = [
        ['first_name' => 'DJAM', 'last_name' => 'MARCEL', 'phone' => '694547521', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-27', 'qualification' => null],
        ['first_name' => 'BERNARD', 'last_name' => 'MASSOCK', 'phone' => '+237654916761', 'email' => null, 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2025-10-22', 'qualification' => null],
        ['first_name' => 'LEONNEL STEPHANE', 'last_name' => 'NGAKATH', 'phone' => '+237678339596', 'email' => null, 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'MATHIEU', 'last_name' => 'TCHAMENI', 'phone' => '+237650516446', 'email' => null, 'type' => 'P', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'TOBIE', 'last_name' => 'LISSOTA YOMZAK', 'phone' => '+237694593469', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'PHILIP', 'last_name' => 'NKONGHO TAMBE', 'phone' => '675155315', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'MARGUERITE', 'last_name' => 'PAMOWA MARIE', 'phone' => '674134850', 'email' => null, 'type' => 'V', 'gender' => 'f', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'JAMES', 'last_name' => 'NGANYA TILONG', 'phone' => '698461021', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'EMANE', 'last_name' => 'TAMUNA VERA', 'phone' => '+237652646952', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'NESTOR', 'last_name' => 'KAMTCHOU', 'phone' => '696289883', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'ANDRE', 'last_name' => 'MBOCK NOL', 'phone' => '+237699339644', 'email' => null, 'type' => 'P', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'THIERRY', 'last_name' => 'TIODA', 'phone' => '681039987', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'BOUBAKARY', 'last_name' => 'BRAHIMA', 'phone' => '690701677', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'OSCAR', 'last_name' => 'MAMPASSI', 'phone' => '+237697469756', 'email' => null, 'type' => 'P', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'JOEL', 'last_name' => 'TCHEBEI TCHOUNKE', 'phone' => '678307239', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'ALEXIS', 'last_name' => 'KOUAZE NANA', 'phone' => '+237698091048', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'VIVIEN', 'last_name' => 'NONO GILLES', 'phone' => '696172515', 'email' => null, 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'ROGER CEDRIC', 'last_name' => 'NGANKOUE MANGA', 'phone' => '+237696474808', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'HERVE', 'last_name' => 'YOSSA', 'phone' => '691675326', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'FLORENT', 'last_name' => 'EPOH DAVID', 'phone' => '699174337', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'ROMARIC', 'last_name' => 'NGAPMEU TCHABONG', 'phone' => '679769812', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'JOSEPH SARA', 'last_name' => 'BILONGO\'O BILONGO\'O', 'phone' => '693740710', 'email' => null, 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'GEORGETTE', 'last_name' => 'NDONDOCK NICAISE', 'phone' => '+237674385786', 'email' => null, 'type' => 'V', 'gender' => 'f', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'JACQUELINE', 'last_name' => 'PIEFLEYOU', 'phone' => '655689082', 'email' => null, 'type' => 'P', 'gender' => 'f', 'hire_date' => '2025-08-28', 'qualification' => null],
        ['first_name' => 'CHRISTIAN', 'last_name' => 'KUITCHOU', 'phone' => '697811214', 'email' => 'christian.kuitchou@cpb.cm', 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Mathematiques'],
        ['first_name' => 'MOISE', 'last_name' => 'OWONO MVENG', 'phone' => '+237692179747', 'email' => 'moise.owono@cpb.cm', 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Physique'],
        ['first_name' => 'MILIXANDRE DELFLORE', 'last_name' => 'PETKEU', 'phone' => '+237682149334', 'email' => 'milixandre.petkeu@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Chimie'],
        ['first_name' => 'DONATIEN', 'last_name' => 'NDZANA', 'phone' => '676535028', 'email' => 'donatien.ndzana@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Histoire'],
        ['first_name' => 'SUZANNE', 'last_name' => 'NGO SAMNICK', 'phone' => '683263002', 'email' => 'suzanne.samnick@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Master Francais'],
        ['first_name' => 'SANTANA', 'last_name' => 'MOUKORY', 'phone' => '+237695398487', 'email' => 'santana.moukory@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Anglais'],
        ['first_name' => 'GERAUDE', 'last_name' => 'BATOUANEN MOBAN', 'phone' => '696427010', 'email' => 'geraude.batouanen@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Geographie'],
        ['first_name' => 'NGNINZEKO', 'last_name' => 'BOGNI', 'phone' => '696961822', 'email' => 'ngninzeko.bogni@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Biologie'],
        ['first_name' => 'EMERENTIEN', 'last_name' => 'LY-INBE', 'phone' => '688352081', 'email' => 'emerentien.lyinbe@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Philosophie'],
        ['first_name' => 'DESIRE', 'last_name' => 'HOUNSOU', 'phone' => '+237655348453', 'email' => 'desire.hounsou@cpb.cm', 'type' => 'P', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Economie'],
        ['first_name' => 'JEAN', 'last_name' => 'BEKOMBO POUNGOUE', 'phone' => '672939521', 'email' => 'jean.bekombo@cpb.cm', 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Droit'],
        ['first_name' => 'PLACIDE', 'last_name' => 'PLACIDE', 'phone' => '681879734', 'email' => 'placide.placide@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Comptabilite'],
        ['first_name' => 'ULRICH LANDRY', 'last_name' => 'NJIKI', 'phone' => '+237681879734', 'email' => 'ulrich.njiki@cpb.cm', 'type' => 'P', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Informatique'],
        ['first_name' => 'CYRILLE', 'last_name' => 'TATSINKOU TENE', 'phone' => '697957200', 'email' => 'cyrille.tatsinkou@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Chimie'],
        ['first_name' => 'JUDITH FLORE', 'last_name' => 'MEKUATE', 'phone' => '694088658', 'email' => 'judith.mekuate@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Biologie'],
        ['first_name' => 'ELISE', 'last_name' => 'NGANSI WONSSI', 'phone' => '697458185', 'email' => 'elise.ngansi@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Master Anglais'],
        ['first_name' => 'BERTRAND', 'last_name' => 'TONFACK', 'phone' => '674780877', 'email' => 'bertrand.tonfack@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Mathematiques'],
        ['first_name' => 'NESTOR', 'last_name' => 'KAMENI', 'phone' => '+237670403323', 'email' => 'nestor.kameni@cpb.cm', 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Electronique'],
        ['first_name' => 'TALLA', 'last_name' => 'AURELIEN', 'phone' => '+237674831332', 'email' => 'talla.aurelien@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Histoire'],
        ['first_name' => 'MARCEL', 'last_name' => 'WOULINA', 'phone' => '+237658047838', 'email' => 'marcel.woulina@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Geographie'],
        ['first_name' => 'NADEGE', 'last_name' => 'WOUASSI', 'phone' => '+237694270455', 'email' => 'nadege.wouassi@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Francais'],
        ['first_name' => 'DEBORAH', 'last_name' => 'NGO NSEGBE', 'phone' => '695384967', 'email' => 'deborah.ngo@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Secretariat'],
        ['first_name' => 'SOLANGE', 'last_name' => 'BI', 'phone' => '+237670609624', 'email' => 'solange.bi@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Anglais'],
        ['first_name' => 'DJOMATCHOUA', 'last_name' => 'SANDRINE', 'phone' => '+237674536333', 'email' => 'djomatchoua.djomatchoua@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Master Economie'],
        ['first_name' => 'SANDRINE NATHALIE', 'last_name' => 'NOUBISSIE', 'phone' => '696976171', 'email' => 'sandrine.noubissie@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Droit'],
        ['first_name' => 'SA', 'last_name' => 'BEITI A MOUBIE', 'phone' => '+237678963262', 'email' => 'sa.beiti@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Comptabilite'],
        ['first_name' => 'PATIENCE', 'last_name' => 'ZOUYA', 'phone' => '696976171', 'email' => 'patience.nzouya@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Philosophie'],
        ['first_name' => 'UGUETTE PHILOMENE', 'last_name' => 'MADADJEU', 'phone' => '697345879', 'email' => 'uguette.madadjeu@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Master Biologie'],
        ['first_name' => 'K', 'last_name' => 'NOUTAMOUN', 'phone' => '+237675120578', 'email' => 'k.noutamoun@cpb.cm', 'type' => 'SP', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Mathematiques'],
        ['first_name' => 'LUCIENNE FLORE', 'last_name' => 'LUCIENNE FLORE', 'phone' => '696118030', 'email' => 'lucienne.flore@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Secretariat'],
        ['first_name' => 'IDA CLAUDINE', 'last_name' => 'MAKOUPO TALLA', 'phone' => '+237697345879', 'email' => 'ida.makoupo@cpb.cm', 'type' => 'SP', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Francais'],
        ['first_name' => 'JOSEPHINE', 'last_name' => 'TCHIEDJIO', 'phone' => '673622646', 'email' => 'josephine.tchiedjio@cpb.cm', 'type' => 'SP', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Master Anglais'],
        ['first_name' => 'TCHUIGANG', 'last_name' => 'MBAKOP', 'phone' => '656287367', 'email' => 'tchuigang.mbakop@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Histoire'],
        ['first_name' => 'PULCHERIE', 'last_name' => 'DJEUKOUA', 'phone' => '675382461', 'email' => 'pulcherie.djeukoua@cpb.cm', 'type' => 'SP', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Comptabilite'],
        ['first_name' => 'ANDRIENNE', 'last_name' => 'GUEKAM', 'phone' => '+237699184325', 'email' => 'andrienne.guekam@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Geographie'],
        ['first_name' => 'LEOCARDIE', 'last_name' => 'TAGNE', 'phone' => '673427073', 'email' => 'leocardie.tagne@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Master Chimie'],
        ['first_name' => 'MERLINE', 'last_name' => 'FOMEKONG KENNE', 'phone' => '692845893', 'email' => 'merline.fomekong@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Physique'],
        ['first_name' => 'YACINTHE', 'last_name' => 'NYANGONO', 'phone' => '655098808', 'email' => 'yacinthe.nyangono@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Electronique'],
        ['first_name' => 'THALES', 'last_name' => 'TCHEUSONG', 'phone' => '655428206', 'email' => 'thales.tcheusong@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Mathematiques'],
        ['first_name' => 'JEAN MARIE', 'last_name' => 'NJINE DEHELALE', 'phone' => '+237679478466', 'email' => 'jean.njine@cpb.cm', 'type' => 'P', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Biologie'],
        ['first_name' => 'RAISSA DANIE', 'last_name' => 'MEBOT', 'phone' => '+237694087843', 'email' => 'raissa.mebot@cpb.cm', 'type' => 'SP', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Francais'],
        ['first_name' => 'STEPHANE EVINDI', 'last_name' => 'FRANCK', 'phone' => '677999266', 'email' => 'stephane.franck@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Informatique'],
        ['first_name' => 'PIUS COLLINS', 'last_name' => 'KOWA', 'phone' => '+237673474441', 'email' => 'pius.kowa@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Anglais'],
        ['first_name' => 'DUMONT', 'last_name' => 'NAWESSI', 'phone' => '+237650466778', 'email' => 'dumont.nawessi@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Histoire'],
        ['first_name' => 'CHRISTIAN', 'last_name' => 'KUIZING', 'phone' => '+237694390490', 'email' => 'christian.kuizing@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Comptabilite'],
        ['first_name' => 'LAURENT', 'last_name' => 'BOUM GWETH', 'phone' => '650824521', 'email' => 'amour.boum@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Geographie'],
        ['first_name' => 'NGOGUE', 'last_name' => 'NGNOGUE', 'phone' => '699893310', 'email' => 'ngogue.ngnogue@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Economie'],
        ['first_name' => 'FREDERIC', 'last_name' => 'FREDERIC', 'phone' => '691015957', 'email' => 'frederic.frederic@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Philosophie'],
        ['first_name' => 'PAUL', 'last_name' => 'NJEM IV', 'phone' => '+237699734094', 'email' => 'paul.njem@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Electronique'],
        ['first_name' => 'ELYSEE', 'last_name' => 'TASSO', 'phone' => '+237699893310', 'email' => 'elysee.tasso@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Mathematiques'],
        ['first_name' => 'JUDITH', 'last_name' => 'DZOKOU KENGNE', 'phone' => '694859867', 'email' => 'judith.dzokou@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Master Biologie'],
        ['first_name' => 'GABRIEL', 'last_name' => 'TCHEKWANDEU', 'phone' => '676373457', 'email' => 'gabriel.tchekwandeu@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Chimie'],
        ['first_name' => 'GENEVIEVE', 'last_name' => 'ONGMETANA', 'phone' => '653675880', 'email' => 'genevieve.ongmetana@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Secretariat'],
        ['first_name' => 'MARCELINE', 'last_name' => 'GUEMDJO', 'phone' => '+237694859867', 'email' => 'marceline.guemdjo@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Francais'],
        ['first_name' => 'ELEONORE', 'last_name' => 'MOMO', 'phone' => '237676373457', 'email' => 'eleonore.momo@cpb.cm', 'type' => 'SP', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Master Anglais'],
        ['first_name' => 'ADELE MIRELLE', 'last_name' => 'NGO NYOBE', 'phone' => '+237693310561', 'email' => 'odele.ngo@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Histoire'],
        ['first_name' => 'GISELE', 'last_name' => 'DJUELA FOKOU', 'phone' => '+237654826532', 'email' => 'gisele.djuela@cpb.cm', 'type' => 'SP', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Comptabilite'],
        ['first_name' => 'LIONIE', 'last_name' => 'LOKIO TCHANANG', 'phone' => '674406870', 'email' => 'lionie.lokio@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Geographie'],
        ['first_name' => 'TOUFFEU', 'last_name' => 'KAMBEU', 'phone' => '698950519', 'email' => 'touffeu.kambeu@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Master Chimie'],
        ['first_name' => 'MIREILLE', 'last_name' => 'MIREILLE', 'phone' => '695148001', 'email' => 'mireille.mireille@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Physique'],
        ['first_name' => 'ERNEST', 'last_name' => 'TCHOUDJIN', 'phone' => '670248900', 'email' => 'ernest.tchoudjin@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Electronique'],
        ['first_name' => 'JULES ARSENE', 'last_name' => 'NDONI', 'phone' => '+237695475535', 'email' => 'jules.ndoni@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => null],
        ['first_name' => 'ARIANE', 'last_name' => 'SIMO TAKONGUE', 'phone' => '+237697320739', 'email' => 'ariane.simo@cpb.cm', 'type' => 'SP', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Master Biologie'],
        ['first_name' => 'RAISSA', 'last_name' => 'RAISSA', 'phone' => '652148494', 'email' => 'raissa.raissa@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Francais'],
        ['first_name' => 'HUBERT', 'last_name' => 'YOUSSA', 'phone' => '697977174', 'email' => 'hubert.youssa@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Informatique'],
        ['first_name' => 'JEAN JACQUES', 'last_name' => 'NGALAKO NJEUNGA', 'phone' => '654377605', 'email' => 'jean.ngalako@cpb.cm', 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Droit'],
        ['first_name' => 'CONSTANT', 'last_name' => 'FOGANG NGOUFO', 'phone' => '+237681613033', 'email' => 'constant.fogang@cpb.cm', 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Anglais'],
        ['first_name' => 'JOSEPH KINDONG', 'last_name' => 'YHAM', 'phone' => '675339919', 'email' => 'joseph.yham@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Histoire'],
        ['first_name' => 'FOMAGNOUA', 'last_name' => 'DC FOTSOP', 'phone' => '+237652148494', 'email' => 'fomagnoua.fotsop@cpb.cm', 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Comptabilite'],
        ['first_name' => 'JOSEPHINE B', 'last_name' => 'JOHNIE', 'phone' => '+237677191795', 'email' => 'josephine.johnie@cpb.cm', 'type' => 'SP', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Geographie'],
        ['first_name' => 'GUSTAVE NOSO', 'last_name' => 'PEKWEKEH', 'phone' => '+237654377605', 'email' => 'gustave.pekwekeh@cpb.cm', 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Economie'],
        ['first_name' => 'DADY JOEL', 'last_name' => 'NKOUAMO', 'phone' => '694848151', 'email' => 'dady.nkouamo@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Philosophie'],
        ['first_name' => 'GERADINE', 'last_name' => 'NDASSI', 'phone' => '672719607', 'email' => 'geradine.ndassi@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Secretariat'],
        ['first_name' => 'WAKUNA', 'last_name' => 'WAKUNA', 'phone' => '652216968', 'email' => 'wakuna.wakuna@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Mathematiques'],
        ['first_name' => 'ERIC', 'last_name' => 'KUMGAHA TANGNI', 'phone' => '674769687', 'email' => 'eric.kumgaha@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Biologie'],
        ['first_name' => 'MIRABEL', 'last_name' => 'MBULLE', 'phone' => '+237677213710', 'email' => 'mirabel.mbulle@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Chimie'],
        ['first_name' => 'JUNIOR', 'last_name' => 'EGBENCHUNG BISONG', 'phone' => '671818252', 'email' => 'junior.egbenchung@cpb.cm', 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2024-12-10', 'qualification' => 'BTS Electronique'],
        ['first_name' => 'PHILIP', 'last_name' => 'NKONGHO TAMBE', 'phone' => '671711951', 'email' => 'philip.nkongho@cpb.cm', 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Physique'],
        ['first_name' => 'GILEAN', 'last_name' => 'ANAM', 'phone' => '654193306', 'email' => 'gilean.anam@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Anglais'],
        ['first_name' => 'ASHU', 'last_name' => 'TEZE', 'phone' => '680093485', 'email' => 'ashu.teze@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Histoire'],
        ['first_name' => 'NDJOH', 'last_name' => 'FRANCK DARIUS', 'phone' => '672126000', 'email' => 'ndjoh.franck@cpb.cm', 'type' => 'P', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Comptabilite'],
        ['first_name' => 'NDJOH', 'last_name' => 'NDJOH', 'phone' => '651074407', 'email' => 'ndjoh.ndjoh@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Droit'],
        ['first_name' => 'ANGELA DIOH', 'last_name' => 'NJINYERU', 'phone' => '674378487', 'email' => 'angela.njinyeru@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Master Francais'],
        ['first_name' => 'GILTON LAISIN', 'last_name' => 'TANTOH', 'phone' => '674193839', 'email' => 'gilton.tantoh@cpb.cm', 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Geographie'],
        ['first_name' => 'PRINCE WILL', 'last_name' => 'LEKEAKA', 'phone' => '673924312', 'email' => 'prince.lekeaka@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Informatique'],
        ['first_name' => 'MIRABEL WEI', 'last_name' => 'MBAIN', 'phone' => '+237654193306', 'email' => 'mirabel.mbain@cpb.cm', 'type' => 'SP', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Mathematiques'],
        ['first_name' => 'THEOPHELEN', 'last_name' => 'ATEMNKENG', 'phone' => '680093485', 'email' => 'theophelen.atemkeng@cpb.cm', 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Economie'],
        ['first_name' => 'COLLIN KOLOA', 'last_name' => 'MOTTO', 'phone' => '672126000', 'email' => 'collin.moto@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Philosophie'],
        ['first_name' => 'GIOVENCE', 'last_name' => 'KOUZO', 'phone' => '+237652546892', 'email' => 'dem.kouzo@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Electronique'],
        ['first_name' => 'JACKON', 'last_name' => 'KADJO', 'phone' => '696118091', 'email' => 'jackon.kadjo@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Histoire'],
        ['first_name' => 'BARTHOLOMEW', 'last_name' => 'TUMBU', 'phone' => '696118092', 'email' => 'bartholomew.tumbu@cpb.cm', 'type' => 'V', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'Master Biologie'],
        ['first_name' => 'SIDONIE', 'last_name' => 'FUH', 'phone' => '696118093', 'email' => 'sidonie.fuh@cpb.cm', 'type' => 'V', 'gender' => 'f', 'hire_date' => '2024-12-12', 'qualification' => 'Licence Francais'],
        ['first_name' => 'ELVIS METOUKE', 'last_name' => 'MESUMBE', 'phone' => '674026283', 'email' => 'elvis.mesumbe@cpb.cm', 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2024-12-12', 'qualification' => 'BTS Comptabilite'],
        ['first_name' => 'PETSAM', 'last_name' => 'LIENOU SERENA', 'phone' => '680305638', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-09-09', 'qualification' => null],
        ['first_name' => 'CLADORE', 'last_name' => 'TCHANDO', 'phone' => '653025600', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-09-09', 'qualification' => null],
        ['first_name' => 'BOREL', 'last_name' => 'NKENFACK', 'phone' => '+237651395036', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-09-16', 'qualification' => null],
        ['first_name' => 'ERICA', 'last_name' => 'MAWOWO', 'phone' => '+237697563793', 'email' => null, 'type' => 'V', 'gender' => 'f', 'hire_date' => '2025-09-16', 'qualification' => null],
        ['first_name' => 'JOSEPH', 'last_name' => 'BINONG', 'phone' => '+237675314058', 'email' => null, 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2025-09-17', 'qualification' => null],
        ['first_name' => 'ALVINE NINA', 'last_name' => 'OLOUNOU', 'phone' => '+237697339007', 'email' => null, 'type' => 'SP', 'gender' => 'f', 'hire_date' => '2025-09-17', 'qualification' => null],
        ['first_name' => 'AROLE', 'last_name' => 'NANGUE', 'phone' => '692142243', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-09-18', 'qualification' => null],
        ['first_name' => 'ERIC', 'last_name' => 'KUNGABA YANGNI', 'phone' => '677577236', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-09-17', 'qualification' => null],
        ['first_name' => 'DAVID', 'last_name' => 'CALEWI', 'phone' => '674598933', 'email' => null, 'type' => 'SP', 'gender' => 'm', 'hire_date' => '2025-09-18', 'qualification' => null],
        ['first_name' => 'HAPPI', 'last_name' => 'YAMDJEU', 'phone' => '677567551', 'email' => null, 'type' => 'V', 'gender' => 'f', 'hire_date' => '2025-09-21', 'qualification' => null],
        ['first_name' => 'IBRAHIMA', 'last_name' => 'BOUBAKARY', 'phone' => '690701677', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-09-21', 'qualification' => null],
        ['first_name' => 'CELESTIN', 'last_name' => 'FOGUETSOP', 'phone' => '695750492', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-09-22', 'qualification' => null],
        ['first_name' => 'MARTIN', 'last_name' => 'KOTTY', 'phone' => '695379218', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-09-24', 'qualification' => null],
        ['first_name' => 'BISONS', 'last_name' => 'EGBECHUNG', 'phone' => '671818250', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-09-25', 'qualification' => null],
        ['first_name' => 'RAMSES', 'last_name' => 'YOUPENDI', 'phone' => '690084538', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-09-25', 'qualification' => null],
        ['first_name' => 'SULTA', 'last_name' => 'KENDZO', 'phone' => '691179210', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-09-24', 'qualification' => null],
        ['first_name' => 'BRENDA', 'last_name' => 'YOMBI', 'phone' => '651280182', 'email' => null, 'type' => 'V', 'gender' => 'f', 'hire_date' => '2025-09-25', 'qualification' => null],
        ['first_name' => 'EKANE', 'last_name' => 'ELIME', 'phone' => '672004908', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-09-25', 'qualification' => null],
        ['first_name' => 'PATRICE', 'last_name' => 'HEUYO', 'phone' => '+237650363795', 'email' => 'Patrice@cpb-douala.com', 'type' => 'P', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'BERTRAND', 'last_name' => 'NJANKO', 'phone' => '675700450', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-10-07', 'qualification' => null],
        ['first_name' => 'ISALINGENE', 'last_name' => 'DIOH', 'phone' => '676559262', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-10-07', 'qualification' => null],
        ['first_name' => 'DESIREE', 'last_name' => 'TCHAMBA', 'phone' => '675998539', 'email' => null, 'type' => 'V', 'gender' => 'f', 'hire_date' => '2025-10-13', 'qualification' => null],
        ['first_name' => 'MICHAEL', 'last_name' => 'ESUA', 'phone' => '682812061', 'email' => null, 'type' => 'P', 'gender' => 'm', 'hire_date' => '2025-10-21', 'qualification' => 'BACHELOR'],
        ['first_name' => 'LEONARD BLAISE', 'last_name' => 'NJAH BERINYUY', 'phone' => '675753550', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-11-05', 'qualification' => null],
        ['first_name' => 'COLLINS CHE', 'last_name' => 'TITA', 'phone' => '678469398', 'email' => null, 'type' => 'V', 'gender' => 'm', 'hire_date' => '2025-11-25', 'qualification' => null],
        ['first_name' => 'YOLANDE', 'last_name' => 'KEMMELONG', 'phone' => '698117967', 'email' => 'kemmelong_yolande@cpb-douala.com', 'type' => 'V', 'gender' => 'f', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'NADYA', 'last_name' => 'MUGHA NKFU', 'phone' => '670875243', 'email' => 'mugha_nkfu_nadya@cpb-douala.com', 'type' => 'V', 'gender' => 'f', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'TITA', 'last_name' => 'ABEH BELTA', 'phone' => '694834008', 'email' => 'abeh_belta_tita@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'FRANCK BAGIO', 'last_name' => 'KEMAYOU MANGWA', 'phone' => '690543513', 'email' => 'kemayou_mangwa_franck@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'JONATHAN', 'last_name' => 'NOUBOUOSSIE POUEGUE', 'phone' => '671628841', 'email' => 'noubouossie_pouegue_jonathan@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'DARCY', 'last_name' => 'HEUMOU', 'phone' => '670139620', 'email' => 'heumou_darcy@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'RAISSA', 'last_name' => 'FOYET', 'phone' => '676330802', 'email' => 'foyet_raissa@cpb-douala.com', 'type' => 'V', 'gender' => 'f', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'GEORGES DUBOIS', 'last_name' => 'BISSAI', 'phone' => '695596631', 'email' => 'bissai_georges@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'FLORIENT', 'last_name' => 'NGANSOP', 'phone' => '655254488', 'email' => 'ngansop_florient@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'VICTOR ULRICH', 'last_name' => 'MILON', 'phone' => '656536558', 'email' => 'milon_victor@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'FRYGZ', 'last_name' => 'KIGNI ANYAM', 'phone' => '670218828', 'email' => 'kigni_anyam_frygz@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'JACQUES LEDOUX', 'last_name' => 'SAMBA PEM', 'phone' => '656474470', 'email' => 'samba_pem_jacques@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'VICTOIRE', 'last_name' => 'MAKA TETA', 'phone' => '678388242', 'email' => 'maka_teta_victoire@cpb-douala.com', 'type' => 'V', 'gender' => 'f', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'MATCHINDE', 'last_name' => 'FOSSO', 'phone' => '677793467', 'email' => 'fosso_matchinde@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'JONGO', 'last_name' => 'NDA ADOLF', 'phone' => '653595977', 'email' => 'nda_adolf_jongo@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'NADEGE', 'last_name' => 'NGIMDO', 'phone' => '654193118', 'email' => 'ngimdo_nadege@cpb-douala.com', 'type' => 'V', 'gender' => 'f', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'MARCEL SHIYNYIH', 'last_name' => 'DZELAFEN', 'phone' => '672677011', 'email' => 'dzelafen_marcel@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'LEONARD MAKAZI', 'last_name' => 'KUGHA', 'phone' => '680078772', 'email' => 'kugha_leonard@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'JASON', 'last_name' => 'KENFACK', 'phone' => '650634735', 'email' => 'kenfack_jason@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'NELSON', 'last_name' => 'TIKU', 'phone' => '675837087', 'email' => 'tiku_nelson@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'DERICK', 'last_name' => 'KADIRI', 'phone' => '671445489', 'email' => 'kadiri_derick@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'VICTOR', 'last_name' => 'FUAH', 'phone' => '677292386', 'email' => 'fuah_victor@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
        ['first_name' => 'JAMES', 'last_name' => 'MBAH SIMO', 'phone' => '657920323', 'email' => 'mbah_simo_james@cpb-douala.com', 'type' => 'V', 'gender' => 'm', 'hire_date' => null, 'qualification' => null],
    ];

    // Personnel administratif et technique (non-enseignants)
    protected array $staff = [
        ['first_name' => 'MANUELLA', 'last_name' => 'COMPTABLE', 'phone' => '+237657256007', 'email' => 'comptable@cpb-douala.com', 'role' => 'comptable', 'gender' => 'f'],
        ['first_name' => 'MEFOBEU', 'last_name' => 'MEFOBEU', 'phone' => '+237690866410', 'email' => 'mefobeu@cpb-douala.com', 'role' => 'comptable', 'gender' => 'm'],
        ['first_name' => 'KOUOH', 'last_name' => 'ASHLEY', 'phone' => '+237655240303', 'email' => 'ashley@cpb-douala.com', 'role' => 'secretaire', 'gender' => 'f'],
        ['first_name' => 'ELONG', 'last_name' => 'ANGE', 'phone' => '699111062', 'email' => 'ange.elong@cpb-douala.com', 'role' => 'secretaire', 'gender' => 'f'],
        ['first_name' => 'SOFFACK', 'last_name' => 'KELLY', 'phone' => '+237651818278', 'email' => 'kelly.soffack@cpb-douala.com', 'role' => 'comptable_superieur', 'gender' => 'f'],
        ['first_name' => 'GABRIEL', 'last_name' => 'TCHEKWANDEU', 'phone' => '676373457', 'email' => 'gabriel.tchekwandeu@cpb-douala.com', 'role' => 'censeur_esg', 'gender' => 'm'],
        ['first_name' => 'NJIEYA', 'last_name' => 'GEORGES', 'phone' => '+237677961395', 'email' => 'georges.njieya@cpb-douala.com', 'role' => 'censeur_esg', 'gender' => 'm'],
        ['first_name' => 'SUZANNE', 'last_name' => 'NGO SAMNICK', 'phone' => '683263002', 'email' => 'suzanne.ngosamnick@cpb-douala.com', 'role' => 'censeur', 'gender' => 'f'],
        ['first_name' => 'MBAH', 'last_name' => 'DICKSON', 'phone' => '+237682238564', 'email' => 'dickson.mbah@cpb-douala.com', 'role' => 'surveillant_secteur', 'gender' => 'm'],
        ['first_name' => 'YAGAI', 'last_name' => 'TIZI', 'phone' => '697838717', 'email' => 'tizi.yagai@cpb-douala.com', 'role' => 'surveillant_secteur', 'gender' => 'm'],
        ['first_name' => 'TAGNE LONGANG', 'last_name' => 'AYMAR', 'phone' => '+237659139934', 'email' => 'aymar.tagne@cpb-douala.com', 'role' => 'surveillant_secteur', 'gender' => 'm'],
        ['first_name' => 'OUANDJI NGANTCHA', 'last_name' => 'IDRISS', 'phone' => '+237672392949', 'email' => 'idriss.ouandji@cpb-douala.com', 'role' => 'surveillant_secteur', 'gender' => 'm'],
        ['first_name' => 'NNOHO', 'last_name' => 'A RIM', 'phone' => '+237693129765', 'email' => 'rim.nnoho@cpb-douala.com', 'role' => 'surveillant_general', 'gender' => 'm'],
        ['first_name' => 'EWOUAWA', 'last_name' => 'PAULINE', 'phone' => '678832064', 'email' => 'pauline.ewouawa@cpb-douala.com', 'role' => 'secretaire', 'gender' => 'f'],
        ['first_name' => 'TCHAMBA', 'last_name' => 'DESIREE', 'phone' => '+237675998539', 'email' => 'desiree.tchamba@cpb-douala.com', 'role' => 'chef_travaux', 'gender' => 'f'],
        ['first_name' => 'LIBONG MATH', 'last_name' => 'KEVIN', 'phone' => '+237655729742', 'email' => 'kevin.libong@cpb-douala.com', 'role' => 'surveillant_secteur', 'gender' => 'm'],
        ['first_name' => 'MEDJEUGOUE KWAMO', 'last_name' => 'LOIC', 'phone' => '695831504', 'email' => 'loic.medjeugoue@cpb-douala.com', 'role' => 'surveillant_secteur', 'gender' => 'm'],
        ['first_name' => 'DAIROU', 'last_name' => 'DAIROU', 'phone' => '674750447', 'email' => 'dairou@cpb-douala.com', 'role' => 'chef_securite', 'gender' => 'm'],
        ['first_name' => 'DALIX', 'last_name' => 'CHRISTIAN', 'phone' => '+237690171930', 'email' => 'christian.dalix@cpb-douala.com', 'role' => 'reprographe', 'gender' => 'm'],
        ['first_name' => 'NGUEYON HUBERT', 'last_name' => 'DEGRANDO', 'phone' => '+237674554142', 'email' => 'degrando.ngueyon@cpb-douala.com', 'role' => 'principal', 'gender' => 'm'],
        ['first_name' => 'ADIBONE', 'last_name' => 'HUGUETTE', 'phone' => '+237694738284', 'email' => 'huguette.adibone@cpb-douala.com', 'role' => 'surveillant_secteur', 'gender' => 'f'],
        ['first_name' => 'DJIOLEU', 'last_name' => 'FRANCK', 'phone' => '691899013', 'email' => 'franck.djioleu@cpb-douala.com', 'role' => 'bibliothecaire', 'gender' => 'm'],
    ];

    public function handle(): int
    {
        $companyId = 2; // CPBD Douala
        $defaultPassword = Hash::make('cpbd2026');
        $created = 0;
        $skipped = 0;
        $counter = User::withoutGlobalScopes()->where('company_id', $companyId)->count();

        $typeMap = [
            'V' => 'enseignant_vacataire',
            'SP' => 'semi_permanent',
            'P' => 'enseignant_titulaire',
        ];

        $sexeMap = [
            'm' => 'M',
            'f' => 'F',
        ];

        foreach ($this->teachers as $teacher) {
            // Deduplication par nom+prenom dans la meme company
            $exists = User::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('first_name', $teacher['first_name'])
                ->where('last_name', $teacher['last_name'])
                ->exists();

            if ($exists) {
                $this->warn("SKIP (doublon): {$teacher['first_name']} {$teacher['last_name']}");
                $skipped++;
                continue;
            }

            // Si email fourni, verifier qu'il n'existe pas deja
            if ($teacher['email']) {
                $emailExists = User::withoutGlobalScopes()
                    ->where('email', $teacher['email'])
                    ->exists();
                if ($emailExists) {
                    $this->warn("SKIP (email existe): {$teacher['email']}");
                    $skipped++;
                    continue;
                }
            }

            $counter++;
            $employeeId = 'CPBD-' . str_pad($counter, 4, '0', STR_PAD_LEFT);

            // Generer un email si absent
            $email = $teacher['email'];
            if (!$email) {
                $first = Str::slug(Str::lower(explode(' ', $teacher['first_name'])[0]), '');
                $last = Str::slug(Str::lower(explode(' ', $teacher['last_name'])[0]), '');
                $email = "{$first}.{$last}@cpb-douala.com";

                // Eviter les doublons d'email genere
                $suffix = 1;
                $baseEmail = $email;
                while (User::withoutGlobalScopes()->where('email', $email)->exists()) {
                    $email = str_replace('@', "{$suffix}@", $baseEmail);
                    $suffix++;
                }
            }

            User::withoutGlobalScopes()->create([
                'employee_id' => $employeeId,
                'first_name' => $teacher['first_name'],
                'last_name' => $teacher['last_name'],
                'email' => $email,
                'password' => $defaultPassword,
                'phone' => $teacher['phone'],
                'employee_type' => $typeMap[$teacher['type']] ?? 'vacataire',
                'sexe' => $sexeMap[$teacher['gender'] ?? ''] ?? null,
                'specialite' => $teacher['qualification'],
                'date_embauche' => $teacher['hire_date'],
                'company_id' => $companyId,
                'is_active' => true,
                'qr_token' => Str::uuid()->toString(),
            ]);

            $typeName = $typeMap[$teacher['type']] ?? 'V';
            $this->info("OK: {$employeeId} - {$teacher['first_name']} {$teacher['last_name']} ({$typeName})");
            $created++;
        }

        $this->newLine();
        $this->info("=== Enseignants: {$created} crees, {$skipped} ignores ===");

        // ========== PERSONNEL ADMINISTRATIF ==========
        $this->newLine();
        $this->info('--- Import du personnel administratif ---');
        $staffCreated = 0;
        $staffSkipped = 0;

        foreach ($this->staff as $person) {
            $nameParts = explode(' ', $person['first_name'], 2);
            $firstName = $person['first_name'];
            $lastName = $person['last_name'];

            $exists = User::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('first_name', $firstName)
                ->where('last_name', $lastName)
                ->exists();

            if ($exists) {
                $this->warn("SKIP staff (doublon): {$firstName} {$lastName}");
                $staffSkipped++;
                continue;
            }

            if ($person['email']) {
                $emailExists = User::withoutGlobalScopes()
                    ->where('email', $person['email'])
                    ->exists();
                if ($emailExists) {
                    $this->warn("SKIP staff (email existe): {$person['email']}");
                    $staffSkipped++;
                    continue;
                }
            }

            $counter++;
            $employeeId = 'CPBD-' . str_pad($counter, 4, '0', STR_PAD_LEFT);

            User::withoutGlobalScopes()->create([
                'employee_id' => $employeeId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $person['email'],
                'password' => $defaultPassword,
                'phone' => $person['phone'],
                'employee_type' => 'administratif',
                'sexe' => $sexeMap[$person['gender'] ?? ''] ?? null,
                'specialite' => $person['role'],
                'company_id' => $companyId,
                'is_active' => true,
                'qr_token' => Str::uuid()->toString(),
            ]);

            $this->info("OK staff: {$employeeId} - {$firstName} {$lastName} ({$person['role']})");
            $staffCreated++;
        }

        $this->newLine();
        $this->info("=== Personnel: {$staffCreated} crees, {$staffSkipped} ignores ===");
        $this->info("=== TOTAL: " . ($created + $staffCreated) . " crees, " . ($skipped + $staffSkipped) . " ignores ===");

        return Command::SUCCESS;
    }
}
